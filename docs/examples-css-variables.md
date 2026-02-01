# CSS Variables Usage Examples

## Quick Reference

Three ways to provide CSS variables to the `/convert-html` endpoint:

| Method | Parameter | Use Case |
|--------|-----------|----------|
| **Direct Parameter** | `css_variables` | Variables from external source (DB, API) |
| **HTML Extraction** | `import_variables: true` | Self-contained HTML with `<style>` tags |
| **Combined** | Both parameters | Base variables + HTML-specific variables |

---

## Method 1: Direct Parameter (css_variables)

### Basic Example

```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<div style=\"color: var(--brand-color); font-size: var(--text-size);\">Welcome</div>",
    "css_variables": "--brand-color: #3498db; --text-size: 18px;"
  }'
```

**Result:**
- Variables "brand-color" (#3498db) and "text-size" (18px) created
- var() references preserved in widget

### When to Use
✅ Variables stored in database
✅ Variables from external API
✅ Dynamically generated variables
✅ Clean separation from HTML

---

## Method 2: HTML Extraction (import_variables)

### Basic Example

```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --brand-color: #3498db; --text-size: 18px; }</style><div style=\"color: var(--brand-color);\">Welcome</div>",
    "import_variables": true
  }'
```

**Result:**
- Variables extracted from `:root` selector
- Both variables created and available

### When to Use
✅ Self-contained HTML documents
✅ Variables defined with selectors
✅ Multiple `<style>` blocks
✅ Variables scoped to different selectors

---

## Method 3: Combined Sources

### Basic Example

```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>.theme { --accent: #e74c3c; }</style><div style=\"color: var(--brand); background: var(--accent);\">Text</div>",
    "css_variables": "--brand: #3498db;",
    "import_variables": true
  }'
```

**Result:**
- "brand" from `css_variables` parameter
- "accent" from HTML `<style>` tag
- Both available for use

### When to Use
✅ Base theme variables from parameter
✅ Component-specific variables from HTML
✅ Maximum flexibility

---

## Real-World Scenarios

### Scenario 1: WordPress Theme Integration

**Context:** Site-wide brand colors stored in WordPress options

```php
// Get brand variables from WordPress
$brand_css = get_option( 'site_brand_variables' );
// Result: "--primary: #3498db; --secondary: #2ecc71;"

// User submits HTML content
$user_html = '<div style="color: var(--primary)">Hello</div>';

// Convert with brand variables
$response = wp_remote_post(
    rest_url( 'html-css-converter/v1/convert-html' ),
    [
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body' => json_encode([
            'html' => $user_html,
            'css_variables' => $brand_css,
        ])
    ]
);
```

---

### Scenario 2: User Theme Selector

**Context:** User can choose light/dark theme

```javascript
const themes = {
  light: '--bg: #ffffff; --text: #000000; --accent: #3498db;',
  dark: '--bg: #1a1a1a; --text: #ffffff; --accent: #e74c3c;'
};

async function convertWithTheme(html, selectedTheme) {
  const response = await fetch('/wp-json/html-css-converter/v1/convert-html', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      html: html,
      css_variables: themes[selectedTheme]
    })
  });

  return await response.json();
}

// Usage
convertWithTheme(
  '<div style="background: var(--bg); color: var(--text);">Content</div>',
  'dark'
);
```

---

### Scenario 3: Design Tokens from External System

**Context:** Design system provides tokens via API

```javascript
// Fetch design tokens
const tokens = await fetch('https://design-system.com/api/tokens');
const tokenData = await tokens.json();

// Convert to CSS variables
const cssVars = Object.entries(tokenData.colors)
  .map(([key, value]) => `--${key}: ${value};`)
  .join(' ');
// Result: "--primary: #3498db; --secondary: #2ecc71;"

// Use in conversion
const result = await fetch('/wp-json/html-css-converter/v1/convert-html', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    html: userGeneratedHTML,
    css_variables: cssVars
  })
});
```

---

### Scenario 4: Component Library with Custom Overrides

**Context:** Base component styles + user customizations

```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>.card { --card-padding: 24px; --card-radius: 8px; }</style><div class=\"card\" style=\"padding: var(--card-padding); color: var(--brand-primary);\">Card Content</div>",
    "css_variables": "--brand-primary: #3498db; --brand-secondary: #2ecc71;",
    "import_variables": true
  }'
```

**Result:**
- Brand colors from parameter (site-wide)
- Component-specific variables from HTML
- Perfect separation of concerns

---

## Comparison Table

| Feature | Direct Parameter | HTML Extraction | Combined |
|---------|-----------------|-----------------|----------|
| **Syntax** | Raw declarations | Selector-wrapped | Both |
| **Source** | External/Dynamic | Embedded in HTML | Both sources |
| **Selectors** | Not needed | `:root`, `.class`, `#id` | Both styles |
| **Reusability** | High | Medium | High |
| **Setup** | One parameter | Set flag | Both |

---

## Tips & Best Practices

### 1. Choose the Right Method

**Use `css_variables` when:**
- Variables come from database/API
- You want clean HTML
- Variables are site-wide/reusable

**Use `import_variables` when:**
- HTML already has `<style>` tags
- Variables are document-specific
- Working with complete HTML documents

**Use both when:**
- You need base variables + custom overrides
- Combining multiple sources
- Maximum flexibility required

---

### 2. Variable Naming Conventions

**Recommended:**
```
--brand-primary
--brand-secondary
--font-size-heading
--spacing-large
```

**Avoid:**
```
--1st-color      (starts with number)
--my color       (spaces not allowed)
--Primary        (use lowercase)
```

---

### 3. Handling Undefined Variables

Both methods check for undefined `var()` references:

```json
{
  "html": "<div style=\"color: var(--undefined)\">Text</div>",
  "css_variables": "--defined: #ff0000;"
}
```

**Response includes warning:**
```json
{
  "success": true,
  "warnings": ["Variable '--undefined' used but not defined"]
}
```

**Best Practice:** Always provide all referenced variables

---

### 4. Performance Considerations

**Direct Parameter (`css_variables`):**
- ⚡ Fast - no HTML parsing needed
- ✅ Ideal for large HTML documents
- ✅ Minimal overhead

**HTML Extraction (`import_variables`):**
- 🔍 Requires HTML parsing
- ⚡ Still fast - simple regex
- ✅ Good for most use cases

**Combined:**
- Same as HTML extraction
- Minimal additional overhead

---

## Error Handling

### Missing Variables

```json
{
  "html": "<div style=\"color: var(--missing)\">Text</div>"
}
```

**Result:** No error, warning in response
**Widget:** var() preserved as-is

---

### Invalid CSS Syntax

```json
{
  "css_variables": "--color: invalid syntax here;"
}
```

**Result:** Variable not imported (skipped)
**No error:** Gracefully handled

---

### Duplicate Variables (Same Value)

```json
{
  "html": "<style>--primary: #ff0000;</style>",
  "css_variables": "--primary: #ff0000;",
  "import_variables": true
}
```

**Result:** Only ONE variable created (value-aware deduplication)

---

### Duplicate Variables (Different Values)

```json
{
  "html": "<style>--primary: #ff0000;</style>",
  "css_variables": "--primary: #00ff00;",
  "import_variables": true
}
```

**Result:** Two variables created:
- `primary` (#ff0000 from html)
- `primary-1` (#00ff00 from parameter)

---

## Summary

The `css_variables` parameter provides a powerful, flexible way to manage CSS variables:

✅ **Three methods** to fit any workflow
✅ **Clean API** - simple, intuitive
✅ **Backward compatible** - existing code unaffected
✅ **Value-aware** - smart deduplication
✅ **Production-ready** - fully tested

**See Also:**
- [css-variables-parameter-feature.md](css-variables-parameter-feature.md) - Complete feature documentation
- [test-variables-integration.md](test-variables-integration.md) - Test scenarios
- [plan-variables-integration.md](plan-variables-integration.md) - Implementation plan
