# New IAM instance profiles can take ~30–60s to propagate before EC2 accepts them.
# Without this, RunInstances may succeed briefly then DescribeInstances fails →
# "collecting instance settings: couldn't find resource".
resource "time_sleep" "ec2_after_instance_profile" {
  depends_on = [aws_iam_instance_profile.ec2_app]

  create_duration = "45s"
}
