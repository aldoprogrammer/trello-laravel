variable "instance_type" {
  description = "Spek EC2 High Performance (Free Trial)"
  default     = "c7i-flex.large"
}

variable "availability_zones" {
  type    = list(string)
  default = ["us-east-1a", "us-east-1b"]
}
