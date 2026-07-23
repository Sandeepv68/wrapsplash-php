# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within WrapSplashPHP, please send an email to **sandeepv@live.com**. All security vulnerabilities will be promptly addressed.

**Please do not report security vulnerabilities through public GitHub issues.**

### What to include

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Acknowledgment**: Within 48 hours
- **Assessment**: Within 1 week
- **Fix or mitigation**: Depends on severity, but targeted for the next release

## Security Considerations

- **Never commit API keys, tokens, or secrets** to the repository.
- WrapSplashPHP does not store or log credentials. Tokens are used per-request only.
- All HTTP communication with the Unsplash API should occur over HTTPS (enforced by Guzzle defaults).
- If you are using the OAuth flow, ensure your `redirectUri` uses HTTPS in production.

## Best Practices

- Store your Unsplash API keys in environment variables or a secure secrets manager.
- Rotate your bearer tokens regularly.
- Use the principle of least privilege when requesting OAuth scopes.
