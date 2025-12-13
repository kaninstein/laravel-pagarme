# Security Policy

## Reporting a Vulnerability

We take the security of Laravel Pagarme seriously. If you discover a security vulnerability, please follow these steps:

### How to Report

**DO NOT** open a public GitHub issue for security vulnerabilities.

Instead, please email security details to:
- **Email**: falecom@joaopedrocoelho.com.br
- **Subject**: [SECURITY] Laravel Pagarme - Brief description

### What to Include

Please include the following information:
- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact
- Any suggested fixes (if you have them)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Depends on severity
  - Critical: 24-48 hours
  - High: 1 week
  - Medium: 2 weeks
  - Low: 1 month

### Security Best Practices

When using this package:

1. **Never commit credentials**: Keep your Pagar.me API keys in `.env` file
2. **Use HTTPS**: Always use HTTPS in production
3. **Tokenization**: Use card tokenization instead of handling raw card data
4. **Webhook Validation**: Validate webhook signatures (coming soon)
5. **Environment Separation**: Use different keys for sandbox and production
6. **Logging**: Be careful not to log sensitive information (card numbers, CVV, etc.)
7. **Rate Limiting**: Implement rate limiting on your payment endpoints
8. **Input Validation**: Always validate and sanitize user input before sending to the API

### Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

### Security Features

This package includes:
- ✅ Secure tokenization support
- ✅ HTTPS-only communication
- ✅ No card data stored locally
- ✅ Automatic retry with exponential backoff
- ✅ Detailed error handling without exposing sensitive data
- ✅ Type-safe DTOs to prevent data leaks

## Hall of Fame

We appreciate security researchers who responsibly disclose vulnerabilities. Your name will be listed here (with your permission) if you report a valid security issue.

---

Thank you for helping keep Laravel Pagarme and its users safe!
