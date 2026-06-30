# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security seriously. If you discover a security vulnerability, please follow these steps:

1. **Do not** disclose the vulnerability publicly
2. Email us at: security@focusedtube.com
3. Include a detailed description of the vulnerability
4. Include steps to reproduce the issue
5. Allow us time to investigate and fix the issue

## Security Best Practices

### For Administrators
- Change default admin password immediately
- Use strong passwords (12+ characters with special characters)
- Enable HTTPS for your site
- Keep the system updated
- Regularly backup your data
- Monitor activity logs
- Use a firewall
- Restrict access to sensitive directories

### For Developers
- Always validate and sanitize user input
- Use prepared statements or JSON validation
- Implement CSRF protection on all forms
- Use output escaping for all user-generated content
- Follow security best practices in code
- Regularly audit code for vulnerabilities
- Keep dependencies updated

### Secure Configuration
```apache
# Protect sensitive directories
<FilesMatch "^(data|logs|cache|backups)/.*$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"