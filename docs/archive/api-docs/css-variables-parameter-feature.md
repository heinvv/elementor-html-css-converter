# CSS Variables Parameter Feature

## Overview

Added support for passing CSS variables as a **separate parameter** to the `/convert-html` endpoint. This provides flexibility in how variables are provided to the conversion process.

---

## Three Ways to Provide Variables

### 1. Direct Parameter (NEW)

Pass variables directly via the `css_variables` parameter:

```json
{
  "html": "<div style='color: var(--primary-color)'>Text</div>",
  "css_variables": "--primary-color: #ff0000; --font-size: 16px;"
}
```

**Use Case:**
- Variables come from external source (database, API, user input)
- HTML doesn't contain variable definitions
- Cleaner separation of concerns

---

### 2. Extract from HTML

Extract variables from `<style>` tags in HTML:

```json
{
  "html": "<style>:root { --primary-color: #ff0000; }</style><div>Text</div>",
  "import_variables": true
}
```

**Use Case:**
- HTML already contains variable definitions
- Self-contained HTML document
- Variables defined with selectors

---

### 3. Combined (Both Sources)

Use both parameter and HTML extraction:

```json
{
  "html": "<style>--from-html: #00ff00;</style><div>Text</div>",
  "css_variables": "--from-param: #ff0000;",
  "import_variables": true
}
```

**Result:** Both `--from-param` and `--from-html` imported

**Use Case:**
- Base variables from parameter
- Additional variables from HTML
- Maximum flexibility

---

## Implementation Details

### Parameter Specification

**Parameter:** `css_variables`
- **Type:** string
- **Required:** false
- **Format:** Raw CSS variable declarations
- **Example:** `"--primary-color: #ff0000; --font-size: 16px;"`

### Processing Order

1. **Import from `css_variables` parameter** (if provided)
2. **Import from HTML `<style>` tags** (if `import_variables: true`)
3. **Combine all imported variables**
4. **Check for undefined var() references**
5. **Convert HTML to widgets** (with var() preserved)

### Variable Deduplication

Variables from both sources are deduplicated using value-aware logic:

```
Parameter:  --primary-color: #ff0000;
HTML:       --primary-color: #ff0000;
Result:     Only one "primary-color" variable created (same value)

Parameter:  --primary-color: #ff0000;
HTML:       --primary-color: #00ff00;
Result:     Two variables: "primary-color" (red), "primary-color-1" (green)
```

---

## Examples

### Example 1: Simple Direct Parameter

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<div style=\"color: var(--brand-color)\">Welcome</div>",
    "css_variables": "--brand-color: #3498db;"
  }'
```

**Result:**
- Variable "brand-color" created with value #3498db
- var() reference preserved in widget
- No warnings

---

### Example 2: Multiple Variables from Parameter

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<h1 style=\"color: var(--heading-color); font-size: var(--heading-size);\">Title</h1>",
    "css_variables": "--heading-color: #2c3e50; --heading-size: 32px;"
  }'
```

**Result:**
- Two variables created: "heading-color" and "heading-size"
- Both var() references preserved

---

### Example 3: Combined Sources

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --spacing: 20px; }</style><div style=\"padding: var(--spacing); color: var(--text-color);\">Text</div>",
    "css_variables": "--text-color: #333333;",
    "import_variables": true
  }'
```

**Result:**
- "text-color" from parameter
- "spacing" from HTML
- Both available for var() resolution

---

### Example 4: With Undefined Warning

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<div style=\"color: var(--defined); background: var(--undefined);\">Text</div>",
    "css_variables": "--defined: #ff0000;"
  }'
```

**Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": [
    "Variable '--undefined' used but not defined"
  ]
}
```

---

## Benefits

### 1. Flexibility
- Choose the source that fits your workflow
- Combine sources when needed

### 2. Separation of Concerns
- Keep variables separate from HTML
- Store variables in database/API
- Generate variables dynamically

### 3. Backward Compatible
- All existing code continues to work
- New parameter is optional
- Default behavior unchanged

### 4. Clean API
- No need to wrap variables in `<style>:root{...}</style>`
- Direct, simple format
- Matches `/import-variables` endpoint format

---

## Use Cases

### Use Case 1: CMS Integration

**Scenario:** Variables stored in WordPress options

```php
$brand_variables = get_option( 'brand_css_variables' );
// "--primary-color: #ff0000; --secondary-color: #00ff00;"

$response = wp_remote_post(
    'wp-json/html-css-converter/v1/convert-html',
    [
        'body' => json_encode([
            'html' => $user_html,
            'css_variables' => $brand_variables,
        ])
    ]
);
```

---

### Use Case 2: Theme System

**Scenario:** User selects theme, variables applied

```javascript
const themes = {
  light: '--bg: #ffffff; --text: #000000;',
  dark: '--bg: #000000; --text: #ffffff;'
};

fetch('/wp-json/html-css-converter/v1/convert-html', {
  method: 'POST',
  body: JSON.stringify({
    html: userContent,
    css_variables: themes[selectedTheme]
  })
});
```

---

### Use Case 3: External Design System

**Scenario:** Variables fetched from design system API

```javascript
const designTokens = await fetch('https://design-system.com/tokens.json');
const cssVars = convertTokensToCSS(designTokens);

const result = await fetch('/wp-json/html-css-converter/v1/convert-html', {
  method: 'POST',
  body: JSON.stringify({
    html: content,
    css_variables: cssVars
  })
});
```

---

## Comparison with Other Methods

### vs. `/import-variables` Endpoint

**import-variables endpoint:**
- Imports variables to Elementor globally
- Separate step before conversion
- Variables persist for all future conversions

**css_variables parameter:**
- Imports variables for this conversion only
- Single-step process
- Can combine with HTML extraction

**When to use each:**
- Use `/import-variables` for global, reusable variables
- Use `css_variables` for conversion-specific variables

---

### vs. HTML `<style>` Tags

**HTML <style> tags:**
- Variables embedded in HTML
- Must use selectors (`:root`, `.class`)
- Extracted when `import_variables: true`

**css_variables parameter:**
- Variables separate from HTML
- Raw declarations (no selectors needed)
- Always imported (no flag needed)

**When to use each:**
- Use HTML `<style>` for self-contained documents
- Use `css_variables` for externally-managed variables

---

## Technical Details

### Modified Files

1. **class-rest-api.php**
   - Added `css_variables` parameter to route args
   - Extract parameter in handler
   - Pass to Html_Converter via options

2. **class-html-converter.php**
   - Import from `css_variables` parameter first
   - Then import from HTML if requested
   - Combine variables from both sources
   - Check undefined references against combined list

### Processing Logic

```php
// STEP 1: Import from parameter
if ( ! empty( $css_variables ) ) {
    $result = $this->import_css_variables( $css_variables, $update_mode );
    $imported_variables = array_merge( $imported_variables, $result['imported'] );
}

// STEP 2: Import from HTML
if ( $import_variables ) {
    $css = $this->extract_css_from_html( $html );
    $result = $this->import_css_variables( $css, $update_mode );
    $imported_variables = array_merge( $imported_variables, $result['imported'] );
}

// STEP 3: Check for undefined
if ( ! empty( $css_variables ) || $import_variables ) {
    $warnings = $this->check_undefined_variables( $html, $imported_variables );
}
```

---

## Testing

See [test-variables-integration.md](../tests/test-variables-integration.md) for comprehensive test scenarios:

- **Test 13:** Direct css_variables parameter
- **Test 14:** Combined css_variables + import_variables
- **Test 15:** css_variables with undefined warnings

---

## Summary

The `css_variables` parameter provides a flexible, clean way to supply CSS variables to the conversion process. It complements the existing HTML extraction method and enables powerful integration scenarios with CMSs, theme systems, and design tools.

**Key Points:**
- ✅ Optional parameter (backward compatible)
- ✅ Raw CSS variable format (no selectors needed)
- ✅ Combines with HTML extraction
- ✅ Value-aware deduplication
- ✅ Undefined variable warnings
- ✅ Same import logic as `/import-variables` endpoint
