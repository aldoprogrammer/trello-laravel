# IAM role + instance profile for EC2: CodeDeploy agent + SSM (Parameter Store .env)
# https://docs.aws.amazon.com/codedeploy/latest/userguide/getting-started-create-ec2-instance-profile.html

data "aws_region" "current" {}

data "aws_iam_policy_document" "ec2_assume" {
  statement {
    actions = ["sts:AssumeRole"]
    principals {
      type        = "Service"
      identifiers = ["ec2.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "ec2_app" {
  name               = "laravel-ec2-codedeploy-ssm"
  assume_role_policy = data.aws_iam_policy_document.ec2_assume.json
}

resource "aws_iam_role_policy_attachment" "ec2_codedeploy" {
  role       = aws_iam_role.ec2_app.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEC2RoleforAWSCodeDeploy"
}

resource "aws_iam_role_policy_attachment" "ec2_ssm" {
  role       = aws_iam_role.ec2_app.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

resource "aws_iam_instance_profile" "ec2_app" {
  name = "laravel-ec2-codedeploy-ssm"
  role = aws_iam_role.ec2_app.name
}

data "aws_caller_identity" "current" {}

# Allow `aws ssm get-parameter` for `/trello/prod/env_file` (see scripts/restart_server.sh)
resource "aws_iam_role_policy" "ec2_ssm_env_file" {
  name = "laravel-ec2-ssm-env-file"
  role = aws_iam_role.ec2_app.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "ssm:GetParameter",
        "ssm:GetParameters"
      ]
      Resource = "arn:aws:ssm:${data.aws_region.current.id}:${data.aws_caller_identity.current.account_id}:parameter/trello/prod/*"
    }]
  })
}
