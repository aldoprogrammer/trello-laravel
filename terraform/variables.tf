variable "aws_region" {
  description = "AWS region for all resources. AMI must be copied from this same region in the console (top-right region selector)."
  type        = string
  default     = "us-east-1"
}

variable "ami_id" {
  description = "Ubuntu 24.04 x86_64 AMI ID for your region (EC2 console → Launch instance → copy AMI ID). Required — avoids IAM calls for DescribeImages / SSM at plan time."
  type        = string
  validation {
    condition     = can(regex("^ami-[0-9a-f]{8,21}$", var.ami_id))
    error_message = "Set ami_id in terraform.tfvars to a valid AMI id (e.g. Ubuntu 24.04 LTS for your region)."
  }
}

variable "instance_type" {
  description = "EC2 instance type"
  default     = "c7i-flex.large"
}

variable "ec2_key_name" {
  description = "EC2 key pair name in this region (EC2 → Key pairs). Must exist in aws_region or launch fails. Set \"\" to launch without a key (no SSH)."
  type        = string
  default     = "laravel-trello"
}

variable "availability_zones" {
  type    = list(string)
  default = ["us-east-1a", "us-east-1b"]
}

variable "db_password" {
  description = "RDS master user password — override in terraform.tfvars (never commit real passwords)"
  type        = string
  sensitive   = true
  default     = "PasswordRahasia123"
}

variable "enable_rds_read_replica" {
  description = "If true, create a MySQL read replica (extra monthly cost). Laravel DB_SLAVE_HOST should point to this endpoint in SSM .env"
  type        = bool
  default     = false
}
