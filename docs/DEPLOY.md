# Deploy to AWS (CodeDeploy + ALB + RDS)

End-to-end flow **already wired in this repo**:

1. **Terraform** (`terraform/`) — VPC, ALB, 2× EC2, RDS MySQL (optional read replica), security groups.
2. **GitHub Actions** (`.github/workflows/deploy.yml`) — tests on PR/push, then **`aws deploy create-deployment`** to CodeDeploy (code revision from **GitHub**).
3. **CodeDeploy** — pulls the commit from GitHub onto EC2, runs `scripts/restart_server.sh` (AfterInstall hook).
4. **EC2** — `docker compose` runs; production `.env` is loaded from **SSM Parameter Store**.

`k8s/mysql-deployment.yml` is a **standalone** Kubernetes example — **not** part of this CodeDeploy pipeline.

---

## What you need in AWS (e.g. after losing the old IAM user)

### 1. IAM for GitHub Actions (access keys in repo Secrets)

**Console tip:** the policy search box matches **policy names**, not individual actions. Searching `codedeploy:CreateDeployment` returns nothing. Either attach the managed policy **`AWSCodeDeployFullAccess`** (search “CodeDeploy”), or **Create policy** → JSON and paste the policy below.

Create a user (e.g. `github-trello-deploy`) with a minimal policy like below (adjust ARNs for your account):

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "codedeploy:CreateDeployment",
        "codedeploy:GetDeployment",
        "codedeploy:GetApplication",
        "codedeploy:GetDeploymentGroup",
        "codedeploy:ListDeployments",
        "codedeploy:ListApplications"
      ],
      "Resource": "*"
    }
  ]
}
```

In the CodeDeploy console, connect **GitHub → AWS CodeDeploy** (OAuth). The application must use a deployment revision from **GitHub** (not S3), matching the workflow (`--github-location repository=OWNER/REPO,commitId=...`).

Under **GitHub repo → Settings → Secrets and variables → Actions**, set:

| Secret | Value |
|--------|--------|
| `AWS_ACCESS_KEY_ID` | IAM user access key |
| `AWS_SECRET_ACCESS_KEY` | Secret key |
| `CODEDEPLOY_APP_NAME` | (optional) defaults to `TrelloApp` if unset |
| `CODEDEPLOY_DEPLOYMENT_GROUP` | (optional) defaults to `TrelloProdGroup` if unset |

The workflow region is **`us-east-1`** (see the workflow file); keep it aligned with Terraform/CodeDeploy.

---

### 2. CodeDeploy on EC2

**You do not create random EC2 instances by hand for this flow.** This repo’s **Terraform** already provisions **two** `aws_instance.laravel_server` instances (plus ALB, RDS, etc.). After `terraform apply`, those are the targets for your CodeDeploy deployment group.

**What Terraform now includes (see `terraform/iam-ec2.tf` + `user_data` in `main.tf`):**

- An **IAM instance profile** on each EC2 with:
  - `AmazonEC2RoleforAWSCodeDeploy` (CodeDeploy agent)
  - `AmazonSSMManagedInstanceCore` (SSM / Session Manager)
  - Inline allow **`ssm:GetParameter`** on `parameter/trello/prod/*` (for `/trello/prod/env_file`)
- **First-boot `user_data`** installs Docker, Nginx, and the **CodeDeploy agent** for the current region.

So for **new** instances created by Terraform, you normally **do not** SSH in to install the agent—it is already there after the instance finishes booting.

**If you already had EC2 instances created before this IAM/user_data existed:** SSH into each instance once and run:

```bash
sudo apt-get install -y ruby-full wget
cd /tmp
wget https://aws-codedeploy-us-east-1.s3.us-east-1.amazonaws.com/latest/install
chmod +x ./install
sudo ./install auto
sudo service codedeploy-agent status
```

(Replace `us-east-1` in the URL if your region differs.) Attach an instance profile with the same policies as in `terraform/iam-ec2.tf`, or replace the instances with a fresh `terraform apply` so they pick up the profile + script.

**GitHub-sourced revisions:** the agent still needs the EC2 role above; AWS connects GitHub to CodeDeploy in the console—no S3 bucket on your side for the GitHub flow.

---

### 3. SSM — production `.env`

Create a **SecureString** parameter (name used in the script: `/trello/prod/env_file`) containing the **full `.env` file** for the containers, including at minimum:

- `APP_KEY=base64:...` (keep stable; do not regenerate on every deploy)
- `APP_URL` — public URL (ALB), e.g. `http://your-alb-dns`
- `DB_HOST` — **RDS primary** endpoint (from `terraform output rds_endpoint`)
- `DB_SLAVE_HOST` — **read replica** endpoint if `enable_rds_read_replica = true` (`terraform output rds_replica_endpoint`), or the same as primary if you do not use a replica
- `REDIS_HOST`, `MEILISEARCH_HOST` — match your stack (Elastiache, separate EC2, or compose services on the same host)

---

### 4. Terraform

```powershell
cd terraform
terraform init
terraform plan
terraform apply
```

- RDS password: variable `db_password` (development default in `variables.tf` — **override** via `terraform.tfvars` in production).
- Read replica: set `enable_rds_read_replica = true` in `terraform.tfvars` (extra cost; primary gets `backup_retention_period = 7` days automatically).

ALB target group health check uses **`/api/health`** (Laravel API route).

---

### 5. Deploy path

1. Push to `main` → workflow runs **test** then **deploy**.
2. CodeDeploy creates a deployment for that commit.
3. On EC2, `appspec.yml` copies the repo to `/home/ubuntu/trello-laravel` and runs **`scripts/restart_server.sh`** (SSM pull, `docker compose up`, migrate, cache).

---

## Quick troubleshooting

| Symptom | Check |
|--------|--------|
| `create-deployment` fails | GitHub connected to CodeDeploy; app and deployment group names correct; IAM allows `codedeploy:CreateDeployment` |
| Target never healthy on ALB | EC2 security group only allows ALB SG; target port **80**; health path `/api/health` |
| Container cannot reach DB | RDS SG only allows EC2 SG; `DB_HOST` in SSM is the RDS endpoint (not `db` like local Docker) |
| SSM permission denied | EC2 instance role allows `ssm:GetParameter` on that parameter |

---

## One-line summary

**Terraform provisions ALB + EC2 + RDS; GitHub Actions deploys via CodeDeploy; EC2 runs Docker Compose and loads secrets from SSM.**
