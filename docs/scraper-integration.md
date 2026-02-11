# Scraper Workflow Integration

This document explains how to use the WordPress REST API endpoints to trigger GitHub Actions scraper workflows and receive results.

## Overview

The integration allows you to:
1. Trigger a GitHub Actions scraper workflow from WordPress
2. Receive scrape results back in WordPress
3. Retrieve stored results by job ID

## Setup

### 1. WordPress Configuration

#### Secrets (Not Stored in WordPress)

**Secrets must be configured via WordPress constants or environment variables** (not stored in WordPress database):

1. **GitHub Token** (`EHCC_GITHUB_TOKEN`)
   - **Where to set**: 
     - **Option 1**: Add to `wp-config.php`: `define( 'EHCC_GITHUB_TOKEN', 'your-token-here' );`
     - **Option 2**: Set as environment variable: `EHCC_GITHUB_TOKEN=your-token-here`
   - **What it is**: Personal Access Token with `repo` scope
   - **Create at**: https://github.com/settings/tokens
   - **Purpose**: Authenticates GitHub API calls to trigger workflows

2. **Webhook Secret** (`EHCC_WEBHOOK_SECRET`)
   - **Where to set**:
     - **Option 1**: Add to `wp-config.php`: `define( 'EHCC_WEBHOOK_SECRET', 'your-secret-here' );`
     - **Option 2**: Set as environment variable: `EHCC_WEBHOOK_SECRET=your-secret-here`
   - **What it is**: Shared secret string (32+ characters recommended)
   - **Generate**: Use `openssl rand -hex 32` or similar tool
   - **Purpose**: Authenticates webhook requests from GitHub Actions
   - **How it works**: GitHub Actions sends this secret in `X-Webhook-Secret` header when posting results back to WordPress
   - **Important**: Must match the `WEBHOOK_SECRET` value in GitHub (see step 2)

#### Settings (Stored in WordPress)

Go to **WordPress Admin** → **Settings** → **Scraper Settings** and configure:

- **GitHub Repository**: Format `owner/repo` (e.g., `heinvv-abangani/elementor-playwright-scraper`)
  - Stored as: `ehcc_github_repo` WordPress option

### 2. GitHub Repository Configuration

Add the following secret to your GitHub repository:

- **WEBHOOK_SECRET**: 
  - **Where defined**: GitHub repository secret (Settings → Secrets and variables → Actions)
  - **Value**: Must be the exact same string as "Webhook Secret" in WordPress settings
  - **Purpose**: Used by GitHub Actions workflow to authenticate when sending results back to WordPress
  - **How it works**: GitHub Actions includes this value in the `X-Webhook-Secret` header when POSTing to the WordPress webhook endpoint
  - **Setup**: Go to Repository → Settings → Secrets and variables → Actions → New repository secret

## API Endpoints

### 1. Trigger Scrape (trigger-import)

**Endpoint:** `POST /wp-json/html-css-converter/v1/trigger-import`

**Authentication:** None (publicly accessible)

**Request Body:**
```json
{
  "url": "https://external-site.com/page-url",
  "selectors": ".hero, .card",
  "timeout": "60",
  "elementor_base_url": "http://elementor.local/",
  "wordpress_website_url": "https://mysite.com"
}
```

**Parameters:**
- `url` (required): External website URL to scrape (e.g., `https://external-site.com/page-url`)
- `selectors` (required): Comma-separated CSS selectors
- `timeout` (optional): Page load timeout in seconds (default: 60)
- `elementor_base_url` (optional): Elementor converter base URL
- `wordpress_website_url` (required): WordPress website URL where results should be sent (e.g., `https://mysite.com`)

**Note:** The webhook URL is constructed from `wordpress_website_url` + `/wp-json/html-css-converter/v1/import-results`. Results will be sent to the specified WordPress website.

**Breakpoints:** The endpoint automatically adds a `breakpoints` array to the payload sent to the scraper. Breakpoints are read from Elementor's configuration (enabled max-width breakpoints only). This enables the scraper to capture responsive styles at tablet and mobile viewports. Format: `[ { "name": "tablet", "width": 1024, "direction": "max" }, ... ]`.

**Response:**
```json
{
  "success": true,
  "message": "Scraper triggered",
  "job_id": "wp-1234567890-abc12345",
  "github_repo": "owner/repo",
  "webhook_url": "https://yoursite.com/wp-json/html-css-converter/v1/import-results",
  "actions_url": "https://github.com/owner/repo/actions"
}
```

The endpoint POSTs to the configured scraper endpoint (e.g. Vercel), which triggers the GitHub Actions workflow. The `client_payload` includes `breakpoints` from Elementor's configuration for responsive scraping.

### 2. Breakpoints (for scraper)

**Endpoint:** `GET /wp-json/html-css-converter/v1/breakpoints`

**Authentication:** None

**Response:**
```json
{
  "breakpoints": [
    { "name": "tablet", "width": 1024, "direction": "max" },
    { "name": "mobile", "width": 767, "direction": "max" }
  ]
}
```

When the scraper runs locally with `ELEMENTOR_BASE_URL` set, it fetches breakpoints from this endpoint to enable responsive style capture. Values match Elementor → Settings → Style → Responsive Breakpoints.

### 3. Receive Results (Webhook)

**Endpoint:** `POST /wp-json/html-css-converter/v1/import-results`

**Authentication:** None (publicly accessible)

**Request Headers:**
- `Content-Type`: `application/json`

**Note:** This endpoint receives results from GitHub Actions. Webhook secret validation will be handled later.

**Request Body:** (sent by GitHub Actions)
```json
{
  "job_id": "wp-1234567890-abc12345",
  "results": { ... },
  "workflow_run_id": "1234567890",
  "status": "success"
}
```

### 4. Get Results

**Endpoint:** `GET /wp-json/html-css-converter/v1/import-results/{job_id}`

**Authentication:** None (publicly accessible)

**Response:**
```json
{
  "success": true,
  "job_id": "wp-1234567890-abc12345",
  "data": {
    "job_id": "wp-1234567890-abc12345",
    "results": { ... },
    "workflow_run_id": "1234567890",
    "status": "success"
  },
  "received": "2024-01-01 12:00:00"
}
```

## Usage Example

### JavaScript/TypeScript

```javascript
const response = await fetch('/wp-json/html-css-converter/v1/trigger-import', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    url: 'https://external-site.com/page-url',
    selectors: '.hero, .card',
    timeout: '60',
    wordpress_website_url: 'https://mysite.com'
  })
});

const data = await response.json();
const jobId = data.job_id;

// Poll for results
const checkResults = async () => {
  const resultResponse = await fetch(
    `/wp-json/html-css-converter/v1/import-results/${jobId}`
  );
  
  if (resultResponse.ok) {
    const results = await resultResponse.json();
    console.log('Scrape results:', results.data);
  } else {
    setTimeout(checkResults, 2000);
  }
};

checkResults();
```

### PHP

```php
$response = wp_remote_post(rest_url('html-css-converter/v1/trigger-import'), [
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => wp_json_encode([
        'url' => 'https://external-site.com/page-url',
        'selectors' => '.hero, .card',
        'timeout' => '60',
        'wordpress_website_url' => 'https://mysite.com',
    ]),
]);

$data = json_decode(wp_remote_retrieve_body($response), true);
$job_id = $data['job_id'];

// Get results
$results_response = wp_remote_get(
    rest_url("html-css-converter/v1/import-results/{$job_id}")
);

$results = json_decode(wp_remote_retrieve_body($results_response), true);
```

## Workflow

1. WordPress endpoint receives trigger request
2. WordPress calls GitHub API to trigger workflow via `repository_dispatch`
3. GitHub Actions workflow runs scraper
4. Workflow sends results back to WordPress webhook endpoint
5. WordPress stores results with job ID
6. Client can retrieve results using job ID

## Security

**Current Status**: Endpoints are publicly accessible with no authentication.

**Future Security** (to be implemented):
- GitHub token authentication for triggering workflows
- Webhook secret validation for receiving results
- Request token validation for request-response binding

**Results Storage**:
- Results stored in WordPress options table (`ehcc_import_results`)
- Can be retrieved by job ID using the GET endpoint

## Troubleshooting

### Workflow not triggering

- Check GitHub token has `repo` scope
- Verify repository name format: `owner/repo`
- Check GitHub Actions tab for workflow runs

### Results not received

- Verify `WEBHOOK_SECRET` matches in WordPress and GitHub
- Check webhook URL is accessible from GitHub (public URL required)
- Review GitHub Actions logs for webhook delivery errors
- Ensure WordPress site is publicly accessible

### Results not found

- Verify job ID is correct
- Check if workflow completed successfully
- Results are stored for 30 days (configurable)
