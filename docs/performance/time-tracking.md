# Import Process Time Tracking

Instrumentation for measuring duration of each step in the Import Website flow. Use this to identify bottlenecks and prioritize optimization.

## Where to Find the Logs

### Browser Console

After an import completes (success or error), timings are logged via `console.table()` in the browser DevTools Console.

1. Open Developer Tools (F12 or Cmd+Option+I).
2. Open the **Console** tab.
3. Trigger an import from the Import Website modal.
4. When the flow completes, a timing table appears in the console.

### Network Tab (WordPress Server Timing)

When `EHCC_DEBUG_TIMING` or `WP_DEBUG` is enabled, timing data is included in REST API responses:

1. Open Developer Tools → **Network** tab.
2. Trigger an import.
3. Inspect responses:
   - **POST** to `trigger-import`: `timing.wp_to_scraper_ms`
   - **POST** to `convert-html`: full `timing` object with PHP step durations

## Enabling WordPress Server Timing

Add to `wp-config.php`:

```php
define( 'EHCC_DEBUG_TIMING', true );
```

Or use existing `WP_DEBUG` when it is already defined and true.

---

## End-to-End Flow

```
User clicks Import
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ CLIENT: useImportSubmit.ts                                                │
│   _start = performance.now()                                             │
│   POST /trigger-import ──────────────────────────────────────────────────│
│       │                                                                   │
│       ▼                                                                   │
│   trigger_import_ms = now - t0                                            │
└──────────────────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ WORDPRESS: class-import-rest-api.php → trigger_import()                  │
│   wp_remote_post() to Cloud Run (when debug: wp_to_scraper_ms)            │
└──────────────────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ CLOUD RUN: server.ts → handleScrape()                                     │
│   handleStart = now                                                       │
│   breakpoints_fetch_ms (if baseUrl set, breakpoints empty)                │
│   command.run() → scrape + build HTML                                     │
│   store_results_ms, total_ms                                               │
│   Payload saved to GCS: { job_id, results, timing, ... }                   │
└──────────────────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ CLIENT: useImportPolling.ts                                               │
│   pollingStart = now                                                      │
│   Every 3s: GET {scraperEndpoint}/results/{jobId}                         │
│   On success: polling_wait_ms, poll_attempts                              │
│   Merge data.timing from poll response                                    │
└──────────────────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ CLIENT: useImportPolling.ts                                               │
│   convertT0 = now                                                         │
│   POST /convert-html (html, css_variables, save_as_template)              │
│   convert_html_ms = now - convertT0                                       │
│   Merge convertData.timing from response                                  │
└──────────────────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ WORDPRESS: class-rest-api.php, class-html-converter.php                  │
│   convert_html_to_atomic_widgets: extract_css, import_variables,          │
│     parse_html, import_classes, create_widgets, integrate_styles,         │
│     import_images, wrap_assign_ids                                        │
│   save_as_template_ms                                                     │
│   Response includes timing when EHCC_DEBUG_TIMING / WP_DEBUG              │
└──────────────────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ CLIENT: useImportPolling.ts                                               │
│   getTemplateT0 = now                                                     │
│   elementorCommon.ajax.addRequest('get_template_data')                     │
│   get_template_data_ms = now - getTemplateT0                              │
│   importT0 = now                                                          │
│   $e.run('document/elements/import')                                      │
│   document_import_ms = now - importT0                                      │
│   total_ms = now - _start                                                 │
│   console.table(timings)                                                   │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Metric Reference

### Client-Side (JavaScript)

| Metric | Source | Measurement | When Logged |
|--------|--------|-------------|-------------|
| `_start` | `useImportSubmit.ts` | `performance.now()` at form submit | Internal; not in output |
| `trigger_import_ms` | `useImportSubmit.ts` | From fetch start to `response.json()` | Success path to polling; catch on error |
| `polling_wait_ms` | `useImportPolling.ts` | From `startPolling` call to first poll returning `status === 'success'` | All exit paths (success, error, timeout, HTTP error) |
| `poll_attempts` | `useImportPolling.ts` | Count of `GET /results/{jobId}` calls before result | All exit paths |
| `convert_html_ms` | `useImportPolling.ts` | From fetch start to `convertResponse.json()` | Success path after poll; error if convert fails |
| `get_template_data_ms` | `useImportPolling.ts` | From `addRequest` call to success callback start | Success callback; error callback on failure |
| `document_import_ms` | `useImportPolling.ts` | From `$e.run` call to return | Success callback only |
| `total_ms` | `useImportPolling.ts` | From `_start` to success callback completion | Success callback only |

Polling config: `pollInterval = 3000` ms, `maxAttempts = 60` (≈3 minutes max wait).

### Scraper (Google Cloud Run)

All scraper metrics are stored in GCS and returned in `GET /results/{jobId}` when `status === 'success'` or `status === 'error'`. The client merges `data.timing` into its timings object.

| Metric | Source | Measurement | When Present |
|--------|--------|-------------|--------------|
| `launch_ms` | `scraper.ts` | `StealthBrowserLauncher.launch()` until return | Every run |
| `goto_ms` | `scraper.ts` | `page.goto(url)` | Every run |
| `session_init_ms` | `scraper.ts` | `sessionManager.initialize(page)` | Every run |
| `process_selectors_ms` | `scraper.ts` | `processSelectors` or `scrapeWithBreakpoints` (selector resolution, element processing, style extraction) | Every run |
| `build_result_ms` | `scraper.ts` | `buildResult()` | Every run |
| `diagnostics_ms` | `scraper.ts` | `DiagnosticsCapture.capture()` | Only when `elements.length === 0` |
| `scrape_ms` | `scraper.ts` | Full `Scraper.run()` duration | Every run |
| `build_html_ms` | `scrape-command.ts` | `converterClient.buildHtmlWithStyles(scrapeResult)` | When elements > 0 |
| `scraper_run_ms` | `scrape-command.ts` | Full `ScrapeCommand.run()` (scrape + build HTML) | Every run |
| `breakpoints_fetch_ms` | `server.ts` | `ScrapeCommand.fetchBreakpointsFromConverter(baseUrl)` | Only when `breakpoints.length === 0` and `baseUrl` set |
| `store_results_ms` | `server.ts` | `bucket.file().save()` to GCS | Every run |
| `total_ms` | `server.ts` | Full `handleScrape()` request duration | Every run |

Note: Scraper `total_ms` is Cloud Run request time. Client `total_ms` is user-perceived end-to-end time from submit to import complete.

### WordPress (PHP)

Recorded only when `( defined('EHCC_DEBUG_TIMING') && EHCC_DEBUG_TIMING ) || ( defined('WP_DEBUG') && WP_DEBUG )`. Returned in `convert-html` JSON response as `timing` and merged into client timings.

| Metric | Source | Measurement | When Present |
|--------|--------|-------------|--------------|
| `wp_to_scraper_ms` | `class-import-rest-api.php` | `wp_remote_post()` to Cloud Run | `trigger-import` response when debug |
| `extract_css_ms` | `class-html-converter.php` | `extract_css_from_html($html)` | Always (when timing enabled) |
| `import_variables_ms` | `class-html-converter.php` | CSS variable import from `css_variables` param and/or extracted CSS via `import_css_variables()` | Always |
| `parse_html_ms` | `class-html-converter.php` | `data_parser->parse_html_for_atomic_widgets()` | Always |
| `import_classes_ms` | `class-html-converter.php` | `import_css_classes()` | Only when `import_classes` option true |
| `create_widgets_ms` | `class-html-converter.php` | `Atomic_Widget_JSON_Creator->create_multiple_widgets()` | Always |
| `integrate_styles_ms` | `class-html-converter.php` | `integrate_styles()` | Always |
| `import_images_ms` | `class-html-converter.php` | `Image_Import_Service->import_images_in_widgets()` | Only when `import_images` option true |
| `wrap_assign_ids_ms` | `class-html-converter.php` | `wrap_non_container_widgets()` + `assign_element_ids_recursive()` | Always |
| `save_as_template_ms` | `class-rest-api.php` | `document_service->save_as_template()` | Only when `save_as_template` is true |

---

## Sample Output (for analysis)

Typical console output after a successful import:

```json
{
  "trigger_import_ms": 28012,
  "polling_wait_ms": 4663,
  "poll_attempts": 1,
  "launch_ms": 3208,
  "goto_ms": 2420,
  "session_init_ms": 225,
  "process_selectors_ms": 20204,
  "build_result_ms": 0,
  "scrape_ms": 26694,
  "build_html_ms": 2,
  "scraper_run_ms": 26892,
  "store_results_ms": 261,
  "total_ms": 46746,
  "convert_html_ms": 3710,
  "extract_css_ms": 0,
  "import_variables_ms": 17,
  "parse_html_ms": 16,
  "create_widgets_ms": 3581,
  "integrate_styles_ms": 0,
  "import_images_ms": 0,
  "wrap_assign_ids_ms": 0,
  "save_as_template_ms": 9,
  "get_template_data_ms": 9785,
  "document_import_ms": 546
}
```

Note: `total_ms` in the final output is the scraper's Cloud Run total. The client overwrites it with end-to-end `total_ms` (form submit → import complete) in the success path.

---

## Metrics That Can Be 0

| Metric | Reason |
|--------|--------|
| `build_result_ms` | Sub-millisecond; rounding to int |
| `build_html_ms` | Tiny DOM string build |
| `extract_css_ms` | No `<style>` tags or very small |
| `integrate_styles_ms` | Minimal style integration |
| `import_images_ms` | `import_images` false or no images |
| `wrap_assign_ids_ms` | Very fast ID assignment |
| `import_classes_ms` | `import_classes` false |
| `diagnostics_ms` | Only when elements matched; otherwise diagnostics_ms present |

---

## Error Path Timing

Timings are logged on all exit paths:

| Path | Metrics Available |
|------|-------------------|
| `trigger-import` fetch throws | `trigger_import_ms`, `_start` removed |
| Poll timeout (60 attempts) | `polling_wait_ms`, `poll_attempts` |
| Poll HTTP error | `polling_wait_ms`, `poll_attempts` |
| Poll returns `status === 'error'` | `polling_wait_ms`, `poll_attempts`, merged `data.timing` |
| No elements matched | `polling_wait_ms`, `poll_attempts`, merged `data.timing` |
| `convert-html` non-200 | `trigger_import_ms`, `polling_wait_ms`, `poll_attempts`, scraper timing, `convert_html_ms` |
| `get_template_data` error | Up to `convert_html_ms`; `get_template_data_ms` recorded |
| Success | Full set including `total_ms` (client), `document_import_ms` |

---

## Why trigger_import_ms Is Not Instant (~28s)

`trigger_import_ms` is measured from the client: from when the browser sends `POST /trigger-import` until it receives the response. That entire round-trip includes:

1. **Client → WordPress** – request to your site  
2. **WordPress → Cloud Run** – `wp_remote_post()` to the scraper URL  
3. **Cloud Run** – runs the full scrape (launch browser, navigate, extract, build HTML, store to GCS)  
4. **Cloud Run → WordPress** – HTTP response  
5. **WordPress → Client** – response back to the browser  

The Cloud Run service is **synchronous**. It does not return until the scrape has finished and results are stored. So `trigger_import_ms` is effectively “time until the scrape completes”, not “time to start a job”.

That’s why it’s ~28 seconds: it’s the full scrape duration plus network latency, not just a quick “trigger and return”.

### Reducing trigger_import_ms: Async Fire-and-Forget

To get `trigger_import_ms` down to ~1–2 seconds, Cloud Run must return before the scrape is done:

1. **Accept POST** – validate payload, generate `job_id`  
2. **Write pending state to GCS** – e.g. `{ status: "pending", job_id }`  
3. **Enqueue background work** – Cloud Tasks → Cloud Run Job, or a self-invoked async request  
4. **Return 200 immediately** – with `job_id` and `scraper_endpoint`  
5. **Background worker** – runs the scrape, overwrites the GCS file with the final result  
6. **Client** – polls `GET /results/{job_id}` as today  

Changes required:

- **New “dispatcher” endpoint** – validates, writes pending file, enqueues work, returns  
- **Cloud Tasks** – to invoke the scraper asynchronously  
- **Cloud Run Job** (or worker service) – performs the scrape and writes results  
- **Results endpoint** – stays as-is (reads from GCS)  

Until this async flow is in place, `trigger_import_ms` will remain roughly equal to `scraper_run_ms` plus network overhead.

---

## process_selectors_ms vs scraper_run_ms

| Metric | Scope | What It Includes |
|--------|--------|------------------|
| **process_selectors_ms** (20,204) | Core scraping logic only | Selector resolution, element matching, style extraction via CDP, viewport changes for breakpoints. The “find elements and get their styles” step. |
| **scraper_run_ms** (26,892) | Full scraper execution | Everything in `ScrapeCommand.run()`: `launch_ms` + `goto_ms` + `session_init_ms` + `process_selectors_ms` + `build_result_ms` + `build_html_ms` |

Breakdown:

```
scraper_run_ms ≈ launch_ms + goto_ms + session_init_ms + process_selectors_ms + build_result_ms + build_html_ms
26,892     ≈ 3,208 + 2,420 + 225 + 20,204 + 0 + 2 ≈ 26,059 (+ overhead)
```

So:

- **process_selectors_ms** – main scraping work (about 75% of scraper time in your sample)  
- **scraper_run_ms** – entire scraper pipeline, including browser startup and HTML building  

---

## Other Improvement Opportunities

| Area | Current | Improvement | Effort |
|------|---------|-------------|--------|
| **trigger_import** | Synchronous (blocks until scrape done) | Async fire-and-forget (see above) | High – Cloud Tasks + Job |
| **launch_ms** (~3.2s) | Cold start on each request | Keep Cloud Run instances warm; use min instances | Medium – config change |
| **process_selectors_ms** (~20s) | Full page scrape, possibly breakpoints | Narrow selectors, fewer elements, disable breakpoints for imports | Low–medium |
| **get_template_data_ms** (~10s) | Elementor loads full template from DB | Preload or cache; reduce template size | Medium – depends on Elementor |
| **create_widgets_ms** (~3.6s) | Full HTML → widget conversion | Profile and optimize `Atomic_Widget_JSON_Creator` | Medium |
| **document_import_ms** (~0.5s) | Elementor DOM update | Usually acceptable | Low priority |

Quick wins:

- **Narrow selectors** – fewer elements → faster `process_selectors_ms`  
- **Fewer breakpoints** – no tablet/mobile if not needed  
- **Cloud Run min instances = 1** – avoids cold start and high `launch_ms`  

---

## Common Bottlenecks

| Symptom | Likely Cause | Direction |
|---------|--------------|-----------|
| High `trigger_import_ms` | Cloud Run cold start, network latency, WordPress → Cloud Run round-trip | Warm instances, minimize cold starts |
| High `launch_ms` | Chromium cold start on Cloud Run | Keep instances warm; consider smaller browser config |
| High `goto_ms` | Slow target page load | Reduce target page size; check target server |
| High `process_selectors_ms` | Many elements, complex selectors, breakpoints (responsive scrape) | Narrow selectors; reduce breakpoints |
| High `convert_html_ms` | WordPress conversion + DB | See PHP metrics below |
| High `create_widgets_ms` | Large HTML, many widgets | Simplify HTML; optimize `Atomic_Widget_JSON_Creator` |
| High `get_template_data_ms` | Elementor loading template from DB | Template size; DB/query performance |
| High `document_import_ms` | Elementor inserting many elements | Document complexity |
| `extract_css_ms`, `integrate_styles_ms` at 0 | Sub-ms or no-op | Normal |

---

## Data Flow

1. **Client** creates `timings`, records `trigger_import_ms`, passes `timings` to `startPolling`.
2. **Polling** records `polling_wait_ms`, `poll_attempts`; merges `data.timing` from scraper response (success or error).
3. **Client** records `convert_html_ms`; merges `convertData.timing` from `convert-html` response.
4. **Client** records `get_template_data_ms`, `document_import_ms`, `total_ms` in success path.
5. **`logTimings`** strips `_start` and calls `console.table(timings)`.

Scraper and WordPress timings are additive: they are merged into the same object, so the final table contains all metrics from all three systems.

---

## Implementation Notes

| Layer | Files |
|-------|-------|
| Client | `assets/js/src/editor/hooks/useImportSubmit.ts`, `useImportPolling.ts` |
| WordPress | `includes/converters/import/class-import-rest-api.php`, `class-import-timing-collector.php`; `includes/converters/classes/class-rest-api.php`; `includes/converters/html/class-html-converter.php` |
| Scraper | `repos/elementor-playwright-scraper/src/server.ts`, `src/scraper/scrape-command.ts`, `src/scraper/scraper.ts` |
