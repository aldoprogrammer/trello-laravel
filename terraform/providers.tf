provider "aws" {
  region = "us-east-1"
}

terraform {
  required_version = ">= 1.0.0"

  # Pastikan bucket 'aldo-terraform-state' sudah ada di S3
  backend "s3" {
    bucket  = "aldo-terraform-state"
    key     = "prod/terraform.tfstate"
    region  = "us-east-1"
    encrypt = true
  }
}
