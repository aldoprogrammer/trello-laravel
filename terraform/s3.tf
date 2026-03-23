resource "aws_s3_bucket" "deploy" {
  bucket = "laravel-trello-deploy-${data.aws_caller_identity.current.account_id}"
  force_destroy = true
}

resource "aws_s3_bucket_lifecycle_configuration" "deploy_cleanup" {
  bucket = aws_s3_bucket.deploy.id

  rule {
    id     = "expire-old-revisions"
    status = "Enabled"
    expiration {
      days = 14
    }
  }
}

output "deploy_bucket" {
  value = aws_s3_bucket.deploy.id
}
