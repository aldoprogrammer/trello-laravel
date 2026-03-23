provider "aws" {
  region = var.aws_region
}

terraform {
  required_version = ">= 1.0.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = ">= 5.0"
    }
    time = {
      source  = "hashicorp/time"
      version = "~> 0.11"
    }
  }

  # Default: local state (no S3 permissions needed). Use S3 for team/CI when you own the bucket.
  backend "local" {
    path = "terraform.tfstate"
  }
}
