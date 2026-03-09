# Terraform Usage (Project Infra)

This folder provisions AWS infrastructure for this project:
- VPC + networking
- Security groups
- 2x EC2 instances
- ALB + target group
- RDS MySQL
- S3 backend state

## Prerequisites

- Terraform installed
- AWS credentials configured (`aws configure` or env vars)
- S3 bucket for backend state already exists:
  - `aldo-terraform-state`

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
