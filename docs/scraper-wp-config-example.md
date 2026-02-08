# WordPress Configuration Example

This document shows how to configure secrets for the scraper integration without storing them in the WordPress database.

## wp-config.php Configuration

Add these constants to your `wp-config.php` file (before the "That's all, stop editing!" line):

```php
// Elementor HTML CSS Converter - Scraper Integration
define( 'EHCC_GITHUB_TOKEN', 'ghp_your_github_personal_access_token_here' );
define( 'EHCC_WEBHOOK_SECRET', 'your-webhook-secret-here' );
```

## Environment Variables (Alternative)

If you prefer environment variables instead of constants, set them in your server environment:

```bash
export EHCC_GITHUB_TOKEN="ghp_your_github_personal_access_token_here"
export EHCC_WEBHOOK_SECRET="your-webhook-secret-here"
```

## Generating Secrets

### GitHub Token

1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Select `repo` scope
4. Copy the token value
5. Add to `wp-config.php` or environment variable

### Webhook Secret

Generate a strong random string:

```bash
openssl rand -hex 32
```

Or using PHP:

```php
bin2hex(random_bytes(32))
```

Copy the generated value and add to `wp-config.php` or environment variable.

## Security Best Practices

1. **Never commit secrets to version control**
   - Add `wp-config.php` to `.gitignore` if it contains secrets
   - Use environment variables in CI/CD environments

2. **Use different secrets for different environments**
   - Development, staging, and production should have different values

3. **Rotate secrets periodically**
   - Update GitHub token if compromised
   - Regenerate webhook secret and update both WordPress and GitHub

4. **Restrict file permissions**
   - `wp-config.php` should have restrictive permissions (e.g., 600)
   - Only server user should have read access

5. **Use environment variables in containerized deployments**
   - Docker, Kubernetes, etc. should use environment variables
   - Avoid hardcoding secrets in configuration files

## Verification

After configuring secrets:

1. Go to **WordPress Admin → Settings → Scraper Settings**
2. Check that both "GitHub Token" and "Webhook Secret" show "✓ Configured"
3. If they show "✗ Not configured", verify:
   - Constants are defined in `wp-config.php` (check spelling, quotes)
   - Environment variables are set and accessible to PHP
   - No syntax errors in `wp-config.php`

## Troubleshooting

### Secrets not detected

- Verify constant names are exactly: `EHCC_GITHUB_TOKEN` and `EHCC_WEBHOOK_SECRET`
- Check for typos in `wp-config.php`
- Ensure constants are defined before WordPress loads
- For environment variables: verify PHP can access them (`getenv()` works)
- Check server configuration allows environment variables

### wp-config.php location

- Usually in WordPress root directory (same level as `wp-content`)
- Some hosting providers use different locations
- Check WordPress `ABSPATH` constant to verify location
