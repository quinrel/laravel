# Security Policy

## Supported Versions

The following versions of `quinrel/laravel` receive security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.2.x   | :white_check_mark: |
| 1.1.x   | :white_check_mark: |
| < 1.1   | :x:                |

## Reporting a Vulnerability

We take the security of `quinrel/laravel` seriously. If you discover a security vulnerability, please report it responsibly:

1. **Email:** Send details to [security@quinrel.com](mailto:security@quinrel.com) or submit a private security advisory through [GitHub Advisories](https://github.com/quinrel/laravel/security/advisories/new).
2. **Do Not Open Public Issues:** Please do not file public GitHub issues for security vulnerabilities.
3. **Information to Include:**
   - Detailed description of the vulnerability.
   - Steps to reproduce or proof-of-concept (PoC).
   - Potential impact and affected versions.
   - Any suggested mitigations.

## Security Practices in Quinrel

- **SSRF Protection:** All external outbound URL requests (crawling, webhook dispatch, inspection) pass through `Quinrel\Laravel\Support\UrlValidator` to prevent Server-Side Request Forgery against private, loopback, or cloud-metadata IP ranges.
- **Access Control:** All dashboard routes and diagnostic APIs are guarded by `view-quinrel` and `manage-quinrel` authorization gates.
- **Privacy & Minimization:** Telemetry and RUM do not collect personally identifiable information (PII). First-party cookie consent complies with privacy regulations.

