# IAM for running this Terraform

Use the **same IAM user** whose access keys you configure in `aws configure` / `AWS_PROFILE` when you run `terraform apply`.

**Custom JSON (paste in IAM → Policies → Create policy → JSON):** see [`terraform-user-policy.json`](terraform-user-policy.json). Attach that policy to your Terraform user. The console policy search matches **policy names**, not action names like `CreateRole` — use **Create policy** with the JSON file instead.

---

## Easiest (personal dev account)

Attach **one** managed policy:

| Policy | When to use |
|--------|-------------|
| **`AdministratorAccess`** | Fastest; only on accounts you own and can afford to misconfigure. |

---

## Still simple, slightly narrower

Attach **both**:

| Policy | Why |
|--------|-----|
| **`IAMFullAccess`** | Create/update roles, instance profiles, inline policies (`laravel-ec2-codedeploy-ssm`, etc.). |
| **`PowerUserAccess`** | EC2, VPC, ALB, RDS, subnets, security groups, and most other services this stack uses. |

`PowerUserAccess` alone is **not** enough — it does **not** allow IAM changes, and this Terraform creates IAM resources.

---

## If you prefer a custom policy (advanced)

You must allow at least (names vary by API version):

- **IAM:** `CreateRole`, `DeleteRole`, `CreateInstanceProfile`, `AddRoleToInstanceProfile`, `PutRolePolicy`, `DeleteRolePolicy`, `AttachRolePolicy`, `DetachRolePolicy`, and related reads.
- **EC2:** VPC, subnets, security groups, instances, key pairs (if referenced), etc.
- **ELB:** Application Load Balancer, listeners, target groups, attachments.
- **RDS:** DB instances, subnet groups (plus `rds:CreateDBInstance` and related).

Easier to use the managed policies above than to hand-craft this.

---

## CI vs laptop

- **GitHub Actions** (CodeDeploy deploy) can use a **different** user with fewer rights (e.g. CodeDeploy + `sts:AssumeRole` if you use OIDC later).
- **Terraform on your PC** needs a user that can **create** infrastructure — usually **`IAMFullAccess` + `PowerUserAccess`**, or **`AdministratorAccess`** on a dev account.

---

## After `terraform apply` fails halfway

Fix IAM on the user, then run **`terraform apply`** again. Terraform will finish or update what was partially created.
