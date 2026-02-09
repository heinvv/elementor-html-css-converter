# Scraper Security Documentation

## Webhook Secret Overview

**What is the Webhook Secret?**

The webhook secret is a shared authentication string between WordPress and GitHub Actions. It ensures that only legitimate GitHub Actions workflows can send results back to your WordPress site.

**Where is it defined?**

1. **WordPress**: 
   - **WordPress constant**: `EHCC_WEBHOOK_SECRET` (defined in `wp-config.php`)
   - **OR environment variable**: `EHCC_WEBHOOK_SECRET`
   - **Not stored in WordPress database** (more secure)
   - **Example in wp-config.php**: `define( 'EHCC_WEBHOOK_SECRET', 'your-secret-here' );`

2. **GitHub**: Stored as repository secret `WEBHOOK_SECRET`
   - Configured in: Repository → Settings → Secrets and variables → Actions
   - Used by: GitHub Actions workflow (`.github/workflows/scrape.yml`)

**How it works:**

```
WordPress (EHCC_WEBHOOK_SECRET constant/env)  ←→  GitHub (WEBHOOK_SECRET)
         (must match exactly)
         
When GitHub Actions sends results:
1. Includes header: X-Webhook-Secret: <WEBHOOK_SECRET>
2. WordPress reads secret from constant/env variable (not database)
3. WordPress validates: Does header value match EHCC_WEBHOOK_SECRET?
4. If yes → Request authenticated
5. If no → Request rejected (401 Unauthorized)
```

## Required Configuration Parameters

### WordPress Configuration

#### Settings (Stored in WordPress Database)

Configure this in **WordPress Admin → Settings → Scraper Settings**:

1. **GitHub Repository** (required)
   - Format: `owner/repo` (e.g., `heinvv-abangani/elementor-playwright-scraper`)
   - Repository where the scraper workflow is located
   - Stored as: `ehcc_github_repo` WordPress option

#### Secrets (NOT Stored in WordPress Database)

**Secrets must be configured via WordPress constants or environment variables:**

1. **GitHub Token** (`EHCC_GITHUB_TOKEN`) (required)
   - **Where defined**: WordPress constant or environment variable (NOT stored in database)
   - **Option 1**: Add to `wp-config.php`: `define( 'EHCC_GITHUB_TOKEN', 'your-token-here' );`
   - **Option 2**: Set as environment variable: `EHCC_GITHUB_TOKEN=your-token-here`
   - **What it is**: Personal Access Token with `repo` scope
   - **Create at**: https://github.com/settings/tokens
   - **Purpose**: Authenticates GitHub API calls to trigger workflows

2. **Webhook Secret** (`EHCC_WEBHOOK_SECRET`) (required)
   - **Where defined**: WordPress constant or environment variable (NOT stored in database)
   - **Option 1**: Add to `wp-config.php`: `define( 'EHCC_WEBHOOK_SECRET', 'your-secret-here' );`
   - **Option 2**: Set as environment variable: `EHCC_WEBHOOK_SECRET=your-secret-here`
   - **What it is**: A shared secret string used to authenticate webhook requests from GitHub Actions
   - **Value**: Strong random string (recommended: 32+ characters)
   - **Generate**: Use `openssl rand -hex 32` or similar tool
   - **Must match**: The `WEBHOOK_SECRET` value in GitHub repository secrets (see below)
   - **Purpose**: Prevents unauthorized access to the webhook endpoint

### GitHub Repository Secrets

Add this secret to your GitHub repository:

- **WEBHOOK_SECRET** (required)
  - **Where defined**: GitHub repository secret (Settings → Secrets and variables → Actions → New repository secret)
  - **What it is**: The same shared secret value as configured in WordPress
  - **Value**: Must be the exact same string as "Webhook Secret" in WordPress settings
  - **Purpose**: Used by GitHub Actions workflow to authenticate when sending results back to WordPress
  - **How it works**: GitHub Actions includes this value in the `X-Webhook-Secret` header when POSTing to the WordPress webhook endpoint
  - **Setup**: Go to Repository → Settings → Secrets and variables → Actions → New repository secret

## Security Model

The implementation uses a **two-layer security approach**:

### Layer 1: Static Webhook Secret (GitHub → WordPress)

- **Where defined**: 
  - WordPress: Constant `EHCC_WEBHOOK_SECRET` (defined in `wp-config.php`) OR environment variable `EHCC_WEBHOOK_SECRET`
  - **Not stored in WordPress database**
  - GitHub: Repository secret `WEBHOOK_SECRET` (Settings → Secrets and variables → Actions)
- **How it works**: 
  - GitHub Actions sends `X-Webhook-Secret` header with the secret value
  - WordPress reads secret from constant/environment variable (not database)
  - WordPress validates header value matches the constant/env variable
  - If values don't match, request is rejected with 401 Unauthorized
- **Purpose**: Provides basic authentication for webhook responses
- **Code location**: Validated in `check_webhook_permissions()` method in `class-scraper-rest-api.php`
- **Security benefit**: Secrets not stored in database, reducing risk of exposure

### Layer 2: Request Token (One-Time Use)

- WordPress generates unique 32-character token per request
- Token stored temporarily (expires after 1 hour)
- Token sent to GitHub in workflow payload
- GitHub sends token back with results
- WordPress validates token matches stored token
- Token is deleted after successful validation (one-time use)

## Security Benefits

1. **Request-Response Binding**: Each response is tied to a specific request
2. **One-Time Use**: Tokens cannot be reused (deleted after validation)
3. **Time-Based Expiration**: Tokens expire after 1 hour
4. **Defense in Depth**: Both static secret and request token must match
5. **Replay Attack Prevention**: Old tokens cannot be reused

## Security Flow

```
1. WordPress receives trigger request
   ↓
2. WordPress generates unique request_token
   ↓
3. WordPress stores token with job_id (expires in 1 hour)
   ↓
4. WordPress sends token to GitHub in payload
   ↓
5. GitHub Actions runs scraper workflow
   ↓
6. GitHub sends results back with:
   - X-Webhook-Secret header (static secret)
   - request_token in payload
   ↓
7. WordPress validates:
   - Static secret matches
   - Request token exists and matches stored token
   - Token hasn't expired
   ↓
8. WordPress deletes token (one-time use)
   ↓
9. WordPress stores results
```

## API Request Parameters

### Trigger Scrape Endpoint

**Required:**
- `url` - External website URL to scrape
- `selectors` - Comma-separated CSS selectors

**Optional:**
- `timeout` - Page load timeout in seconds (default: 60)
- `elementor_base_url` - Elementor converter base URL

**Note:** The `request_token` is automatically generated and handled internally. You don't need to provide it.

## Security Best Practices

1. **Use Strong Secrets**: Generate webhook secret using:
   ```bash
   openssl rand -hex 32
   ```

2. **Keep Secrets Secure**: 
   - Never commit secrets to version control
   - Use environment variables or secure storage
   - Rotate secrets periodically

3. **Monitor Requests**: 
   - Check WordPress logs for failed authentication attempts
   - Monitor GitHub Actions for unusual activity

4. **HTTPS Only**: 
   - Ensure WordPress site uses HTTPS
   - Prevents token interception in transit

5. **Token Expiration**: 
   - Current expiration: 1 hour
   - Adjust `REQUEST_TOKEN_EXPIRY` constant if needed

## Troubleshooting Security Issues

### "Invalid webhook secret" error

**What it means**: The `X-Webhook-Secret` header value doesn't match the WordPress `EHCC_WEBHOOK_SECRET` constant or environment variable.

**Where to check**:
1. **WordPress**: 
   - Check `wp-config.php` for `define( 'EHCC_WEBHOOK_SECRET', '...' );`
   - OR check environment variables for `EHCC_WEBHOOK_SECRET`
   - Verify the value is set (not stored in database)
   - Check for any whitespace or special characters
   - Go to Settings → Scraper Settings to see if it shows "Configured"
2. **GitHub**: Go to Repository → Settings → Secrets and variables → Actions
   - Verify `WEBHOOK_SECRET` secret exists
   - Verify the value exactly matches WordPress constant/environment variable
   - Check for any whitespace or encoding issues
3. **GitHub Workflow**: Check `.github/workflows/scrape.yml`
   - Verify it uses `${{ secrets.WEBHOOK_SECRET }}` in the `X-Webhook-Secret` header
   - Check workflow logs to see what value is being sent

**Common issues**:
- Values don't match exactly (case-sensitive, whitespace differences)
- Secret not set in GitHub repository secrets
- Secret not defined in `wp-config.php` or as environment variable
- Encoding issues (special characters, line breaks)
- WordPress constant/env variable not accessible (permissions, server config)

### "Request token not found" error

- Token may have expired (1 hour limit)
- Token may have already been used (one-time use)
- Check if job_id matches the original request

### "Request token mismatch" error

- Token was modified in transit (unlikely with HTTPS)
- Token belongs to different request
- Verify job_id matches original request

## Security Considerations

### Current Limitations

1. **Token Storage**: Uses WordPress transients (may be cleared by cache plugins)
2. **Single Server**: Assumes single WordPress instance (not load-balanced)
3. **No IP Whitelisting**: Relies on secret + token validation only

### Future Enhancements

- IP whitelisting for GitHub Actions IPs
- HMAC signature validation
- Rate limiting per IP/user
- Audit logging for security events
