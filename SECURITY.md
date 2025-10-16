# Security Policy

## Supported Versions

We actively maintain and provide security updates for the following versions of Spun Web Archive Forge:

| Version | Supported          |
| ------- | ------------------ |
| 0.3.x   | :white_check_mark: |
| 0.2.x   | :x:                |
| 0.1.x   | :x:                |
| < 0.1   | :x:                |

## Reporting a Vulnerability

We take the security of Spun Web Archive Forge seriously. If you discover a security vulnerability, please follow these steps:

### How to Report

1. **DO NOT** create a public GitHub issue for security vulnerabilities
2. **Email us directly** at: security@spunwebtechnology.com
3. **Include the following information:**
   - Description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact assessment
   - Suggested fix (if available)
   - Your contact information

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your report within 48 hours
- **Initial Assessment**: We will provide an initial assessment within 5 business days
- **Regular Updates**: We will keep you informed of our progress every 7 days
- **Resolution Timeline**: We aim to resolve critical vulnerabilities within 30 days

### Responsible Disclosure

We follow responsible disclosure practices:
- We will work with you to understand and resolve the issue
- We will credit you for the discovery (unless you prefer to remain anonymous)
- We ask that you do not publicly disclose the vulnerability until we have released a fix

## Security Best Practices

### For Users

1. **Keep Updated**: Always use the latest version of the plugin
2. **WordPress Security**: Ensure your WordPress installation is up to date
3. **Strong Credentials**: Use strong, unique passwords for your WordPress admin and Archive.org accounts
4. **HTTPS**: Always use HTTPS for your WordPress site
5. **Regular Backups**: Maintain regular backups of your website

### For Developers

1. **Input Validation**: All user inputs are validated and sanitized
2. **Output Escaping**: All outputs are properly escaped
3. **SQL Injection Prevention**: We use prepared statements for all database queries
4. **CSRF Protection**: All forms include WordPress nonce verification
5. **Capability Checks**: All admin functions require appropriate user capabilities

## Security Features

### Built-in Security Measures

- **Direct Access Prevention**: All PHP files include WordPress environment checks
- **Nonce Verification**: All AJAX requests and form submissions are protected with WordPress nonces
- **Capability Checks**: Admin functions require `manage_options` capability
- **Input Sanitization**: All user inputs are sanitized using WordPress functions
- **Output Escaping**: All outputs use appropriate WordPress escaping functions
- **Prepared Statements**: Database queries use `$wpdb->prepare()` to prevent SQL injection

### Data Protection

- **API Credentials**: Archive.org credentials are stored securely in WordPress options
- **Secret Key Encryption**: The Secret Key is encrypted at rest using AES-256-CBC with a key derived from WordPress salts. Plaintext secrets are no longer stored.
- **Automatic Migration**: Existing plaintext secrets are automatically encrypted during upgrade (DB v1.4) and removed from plaintext storage.
- **Submission Data**: Minimal data is stored; only URLs and submission status
- **User Privacy**: No personal user data is transmitted to external services
- **Data Cleanup**: Complete data removal on plugin uninstall

## Security Audit History

### Version 0.3.4 (2025-01-20)
- Enhanced SQL query safety in uninstall process
- Improved database security hardening
- Added proper table name escaping for DROP TABLE operations
- Updated all database operations to use prepared statements

### Version 0.3.5 (2025-01-20)
- Added WordPress environment validation
- Enhanced compatibility helper for security checks
- Improved static analysis configuration for security compliance

## Common Security Considerations

### WordPress Environment
- The plugin requires WordPress to be properly loaded
- Direct file access is prevented with `ABSPATH` checks
- All WordPress security features are leveraged

### Third-Party Integrations
- **Archive.org API**: Only official Internet Archive APIs are used
- **HTTPS Only**: All external API calls use HTTPS
- **Rate Limiting**: Built-in delays prevent API abuse
- **Error Handling**: Secure error handling prevents information disclosure

### File Security
- No file uploads or downloads
- No dynamic file inclusion
- All file paths are validated
- No executable file generation

## Security Contact

For security-related inquiries:
- **Email**: security@spunwebtechnology.com
- **Website**: https://spunwebtechnology.com
- **Response Time**: Within 48 hours for security reports

## Security Resources

- [WordPress Security Handbook](https://developer.wordpress.org/plugins/security/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [WordPress Plugin Security Guidelines](https://developer.wordpress.org/plugins/security/)

## Acknowledgments

We thank the security researchers and community members who help keep Spun Web Archive Forge secure:

- Security researchers who responsibly disclose vulnerabilities
- WordPress security team for their guidelines and tools
- Plugin review team for their security assessments

---

**Last Updated**: January 20, 2025  
**Version**: 0.6.1