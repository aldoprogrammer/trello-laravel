# Terraform Usage (Project Infra)

This folder provisions AWS infrastructure for this project:
- VPC + networking
- Security groups
- 2x EC2 instances
- ALB + target group
- RDS MySQL
- **Local** Terraform state by default (`terraform.tfstate` in this folder; gitignored)

## Prerequisites

- Terraform installed
- AWS credentials configured (`aws configure` or env vars) for `terraform apply`
- Set **`aws_region`** and **`ami_id`** in `terraform.tfvars` (see below). To use a specific IAM user for Terraform, set `AWS_PROFILE` (or default profile) to that user’s credentials.
- IAM user/role must be allowed to create resources in this stack (VPC, EC2, ELB, RDS, IAM roles, etc.).

### Where to get `ami_id` (AWS Console)

1. Open **EC2** in the console: [EC2 Dashboard](https://console.aws.amazon.com/ec2/).
2. **Top-right** → **Region** → choose where you want infra (e.g. **Asia Pacific (Singapore) `ap-southeast-1`** or **Jakarta `ap-southeast-3`**). Put the **same** value in `terraform.tfvars` as `aws_region = "ap-southeast-1"` (example).
3. Click **Launch instance** (you can cancel on the next screen).
4. Under **Application and OS Images**, select **Ubuntu** → **Ubuntu Server 24.04 LTS** (64-bit x86).
5. Copy the **AMI ID** shown next to the image (format `ami-0abc123...`) into `ami_id` in `terraform.tfvars`.

AMI IDs are **per region** — never paste a Virginia AMI into a Singapore deployment.

If you set `aws_region` to something other than `us-east-1`, also set the **same region** in `.github/workflows/deploy.yml` (`configure-aws-credentials` → `aws-region`) and create **CodeDeploy** in that region so CI deploys match your EC2 region.

`terraform apply` still needs permissions for `ec2:RunInstances`, `rds:CreateDBInstance`, `elasticloadbalancing:*`, `iam:*` (for this module), etc.—narrow policies are possible but tedious; use a dev account or scoped roles.

### If `apply` fails with `AccessDenied` on `ec2:CreateVpc`, `iam:CreateInstanceProfile`, `iam:PutRolePolicy`

The IAM user whose keys you use (e.g. default profile) needs rights to **create networking, EC2, ELB, RDS, and IAM roles/profiles**. This stack is not runnable with “CodeDeploy only” or “read-only” users.

**Practical options (pick one):**

1. **Attach AWS managed policies** to that user (broad but works for learning):  
   `IAMFullAccess` + `AmazonEC2FullAccess` + `AmazonRDSFullAccess` + `ElasticLoadBalancingFullAccess` + `AmazonVPCFullAccess`  
   Or, on a **personal dev account only**, `AdministratorAccess`.

2. **Use another user for Terraform** that already has those rights: configure `aws configure --profile your-admin-user` and run with `$env:AWS_PROFILE="your-admin-user"` before `terraform apply`. Keep **narrow** users (e.g. CI deploy only) separate from **infra** users.

3. After fixing IAM, run **`terraform apply` again**. A partial run may have created the IAM role `laravel-ec2-codedeploy-ssm`; Terraform will reconcile on the next apply.

See **[`IAM_PERMISSIONS.md`](IAM_PERMISSIONS.md)** for a short list of which managed policies to attach.

### Error: `collecting instance settings: couldn't find resource` (EC2)

Often caused by **IAM instance profile propagation** (fixed in repo via `time_sleep`) or a **bad key pair name** in the wrong region.

1. **EC2 → Key pairs** (same region as `aws_region`) — create or import **`laravel-trello`**, or set `ec2_key_name = ""` in `terraform.tfvars` to launch without SSH.
2. Run **`terraform init -upgrade`** (adds the `time` provider), then **`terraform apply`** again.
3. If Terraform still thinks instances exist but AWS does not:

   ```powershell
   terraform state rm 'aws_instance.laravel_server[0]' 'aws_instance.laravel_server[1]'
   terraform apply
   ```

## Backend: local vs S3

**Default (`providers.tf`):** `backend "local"` — state file is `terraform/terraform.tfstate`. No S3 bucket required. Fixes **403 Forbidden** when the old bucket name/account/IAM does not match.

**Optional remote state (S3):** create a bucket **you** own (e.g. `mycompany-terraform-state`), add IAM policy allowing `s3:GetObject`, `s3:PutObject`, `s3:ListBucket` on that bucket, then replace the `terraform {}` block in `providers.tf` with:

```hcl
terraform {
  required_version = ">= 1.0.0"
  backend "s3" {
    bucket  = "YOUR_BUCKET_NAME"
    key     = "prod/terraform.tfstate"
    region  = "us-east-1"
    encrypt = true
  }
}
```

Then run `terraform init -migrate-state` (or `-reconfigure` if starting fresh) and follow prompts.

**403 on S3** means: wrong AWS account, bucket missing, or IAM user cannot read `prod/terraform.tfstate` in that bucket—fix IAM or use local backend.

## Commands

Run all commands from project root:

```powershell
cd terraform
```

Initialize providers + backend:

```powershell
terraform init
```

Validate config:

```powershell
terraform validate
```

See execution plan:

```powershell
terraform plan
```

Apply changes:

```powershell
terraform apply
```

Show outputs:

```powershell
terraform output
```

Show one output:

```powershell
terraform output alb_dns_name
```

Destroy infra (dangerous):

```powershell
terraform destroy
```

## Useful Terraform CLI For This Project

Format all files:

```powershell
terraform fmt -recursive
```

Inspect state resources:

```powershell
terraform state list
```

Inspect one resource in state:

```powershell
terraform state show aws_instance.laravel_server[0]
```

Refresh state only:

```powershell
terraform plan -refresh-only
```

Import existing resource example:

```powershell
terraform import aws_security_group.alb_sg sg-xxxxxxxx
```

## Notes

- Current `provider` region is `us-east-1`.
- Backend state is remote in S3 (`prod/terraform.tfstate`).
- If you use Windows and local binary name is `terraform.exe`, command stays `terraform ...` in PowerShell.
