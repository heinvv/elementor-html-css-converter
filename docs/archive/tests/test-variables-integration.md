# Testing CSS Variables Integration

## Overview

This document provides comprehensive test scenarios for the CSS variables integration in both Phase 1 (var() pass-through) and Phase 2 (HTML extraction).

---

## Phase 1: var() Pass-through Tests

### Test 1: Color Variable in CSS-to-Atomic

**Endpoint:** `POST /wp-json/html-css-converter/v1/css-to-atomic`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/css-to-atomic" \
  -H "Content-Type: application/json" \
  -d '{
    "cssString": "color: var(--primary-color);"
  }'
```

**Expected Response:**
```json
{
  "atomic": {
    "text_color": "var(--primary-color)"
  }
}
```

**Verification:**
- The var() reference is preserved (not rejected)
- No customCss fallback

---

### Test 2: Size Variable in CSS-to-Atomic

**Endpoint:** `POST /wp-json/html-css-converter/v1/css-to-atomic`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/css-to-atomic" \
  -H "Content-Type: application/json" \
  -d '{
    "cssString": "font-size: var(--heading-size);"
  }'
```

**Expected Response:**
```json
{
  "atomic": {
    "typography_font_size": {
      "size": "var(--heading-size)",
      "unit": "custom"
    }
  }
}
```

**Verification:**
- var() passed as custom size value
- No parsing error

---

### Test 3: var() with Fallback

**Endpoint:** `POST /wp-json/html-css-converter/v1/css-to-atomic`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/css-to-atomic" \
  -H "Content-Type: application/json" \
  -d '{
    "cssString": "color: var(--brand-color, #ff0000);"
  }'
```

**Expected Response:**
```json
{
  "atomic": {
    "text_color": "var(--brand-color, #ff0000)"
  }
}
```

**Verification:**
- Fallback value preserved in var() reference

---

### Test 4: Multiple Properties with var()

**Endpoint:** `POST /wp-json/html-css-converter/v1/css-to-atomic`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/css-to-atomic" \
  -H "Content-Type: application/json" \
  -d '{
    "cssString": "color: var(--text-color); font-size: var(--text-size); padding: var(--spacing);"
  }'
```

**Expected Response:**
```json
{
  "atomic": {
    "text_color": "var(--text-color)",
    "typography_font_size": {
      "size": "var(--text-size)",
      "unit": "custom"
    },
    "padding": {
      "block-start": { "size": "var(--spacing)", "unit": "custom" },
      "inline-end": { "size": "var(--spacing)", "unit": "custom" },
      "block-end": { "size": "var(--spacing)", "unit": "custom" },
      "inline-start": { "size": "var(--spacing)", "unit": "custom" }
    }
  }
}
```

**Verification:**
- All var() references preserved
- Different property types handle var() correctly

---

## Phase 2: HTML Variable Extraction Tests

### Test 5: Basic Variable Extraction from :root

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --primary-color: #ff0000; --font-size: 16px; }</style><p style=\"color: var(--primary-color); font-size: var(--font-size);\">Test</p>",
    "import_variables": true,
    "update_mode": "create_new"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": [],
  "page_created": true,
  "inserted": true
}
```

**Verification:**
1. Check Elementor > Settings > Global Variables
2. Variables "primary-color" (red) and "font-size" (16px) should exist
3. No warnings (variables defined before use)
4. Widgets created successfully

---

### Test 6: Extract from ALL Selectors

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --color: #ff0000; } .dark { --color: #0000ff; } #custom { --spacing: 20px; }</style><div>Test</div>",
    "import_variables": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": []
}
```

**Verification:**
1. Variables created: "color" (red), "color-1" (blue), "spacing" (20px)
2. All selectors processed (not just :root)

---

### Test 7: Undefined Variable Warning

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --defined: #ff0000; }</style><div style=\"color: var(--defined); background: var(--undefined);\">Test</div>",
    "import_variables": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": [
    "Variable '--undefined' used but not defined"
  ]
}
```

**Verification:**
1. Conversion succeeds (non-blocking warning)
2. Warning included in response
3. Defined variable created successfully

---

### Test 8: Multiple Style Blocks

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --color-a: #ff0000; }</style><div></div><style>.theme { --color-b: #00ff00; }</style>",
    "import_variables": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": []
}
```

**Verification:**
1. Both variables extracted: "color-a" (red), "color-b" (green)
2. Multiple <style> blocks processed

---

### Test 9: Value-Aware Deduplication with HTML Import

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Pre-condition:** Import variable "brand-color: #ff0000" first using /import-variables endpoint

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --brand-color: #ff0000; }</style><div>Test</div>",
    "import_variables": true,
    "update_mode": "create_new"
  }'
```

**Expected Behavior:**
- Reuses existing "brand-color" variable (same value)
- Does NOT create "brand-color-1"

**Verification:**
1. Check variables - only one "brand-color" exists
2. No duplicate created

---

### Test 10: Update Mode

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Pre-condition:** Variable "theme-color: #ff0000" exists

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --theme-color: #00ff00; }</style><div>Test</div>",
    "import_variables": true,
    "update_mode": "update"
  }'
```

**Expected Behavior:**
- Updates existing "theme-color" from red to green
- Does NOT create "theme-color-1"

**Verification:**
1. Variable "theme-color" now has value #00ff00 (green)

---

### Test 11: No Variable Extraction (Default Behavior)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --color: #ff0000; }</style><div>Test</div>"
  }'
```

**Note:** `import_variables` not specified (defaults to false)

**Expected Behavior:**
- Variables NOT extracted from <style> tag
- No variables created in Elementor
- No warnings array in response

**Verification:**
1. Widgets created successfully
2. Response has no "warnings" key
3. No variables added to Elementor

---

## CSS Variables Parameter Tests

### Test 13: Direct CSS Variables Parameter

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<div style=\"color: var(--primary-color); font-size: var(--text-size);\">Test</div>",
    "css_variables": "--primary-color: #ff0000; --text-size: 16px;",
    "update_mode": "create_new"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": [],
  "page_created": true
}
```

**Verification:**
1. Variables "primary-color" and "text-size" created
2. var() references preserved in widgets
3. No warnings (variables defined)

---

### Test 14: css_variables + import_variables Combined

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>:root { --from-html: #00ff00; }</style><div style=\"color: var(--from-param); background: var(--from-html);\">Test</div>",
    "css_variables": "--from-param: #ff0000;",
    "import_variables": true,
    "update_mode": "create_new"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": []
}
```

**Verification:**
1. Both variables created: "from-param" (red) and "from-html" (green)
2. Variables from both sources imported
3. No warnings

---

### Test 15: css_variables with Undefined Reference

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<div style=\"color: var(--defined); background: var(--undefined);\">Test</div>",
    "css_variables": "--defined: #ff0000;"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [...],
  "warnings": [
    "Variable '--undefined' used but not defined"
  ]
}
```

**Verification:**
1. Variable "defined" created
2. Warning for "--undefined" included
3. Conversion succeeds (non-blocking)

---

## Combined Test: Full Workflow

### Test 12: Import Variables, Then Use Them

**Step 1:** Import variables using standalone endpoint
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": "--brand-color: #ff0000; --spacing: 20px; --font-size: 16px;",
    "update_mode": "create_new"
  }'
```

**Step 2:** Convert HTML using those variables
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<div style=\"color: var(--brand-color); padding: var(--spacing); font-size: var(--font-size);\">Styled Text</div>",
    "import_variables": false
  }'
```

**Expected Result:**
- Variables already defined (no warnings)
- var() references preserved in widget atomic props
- Widgets created successfully

---

## Test Checklist

### Phase 1 Tests
- [ ] Color var() accepted (Test 1)
- [ ] Size var() accepted (Test 2)
- [ ] var() with fallback preserved (Test 3)
- [ ] Multiple var() properties (Test 4)

### Phase 2 Tests
- [ ] Extract from :root (Test 5)
- [ ] Extract from ALL selectors (Test 6)
- [ ] Undefined variable warning (Test 7)
- [ ] Multiple style blocks (Test 8)
- [ ] Value-aware deduplication (Test 9)
- [ ] Update mode (Test 10)
- [ ] Default behavior (no extraction) (Test 11)
- [ ] Full workflow (Test 12)

### CSS Variables Parameter Tests
- [ ] Direct css_variables parameter (Test 13)
- [ ] Combined css_variables + import_variables (Test 14)
- [ ] css_variables with undefined warnings (Test 15)

---

## Troubleshooting

### Issue: var() values rejected
**Solution:** Check Color_Value_Parser::is_supported_color_format() returns true for var()

### Issue: Size var() returns null
**Solution:** Check Size_Value_Parser returns `['size' => 'var(...)', 'unit' => 'custom']`

### Issue: Variables not extracted from HTML
**Solution:** Verify `import_variables: true` parameter is set

### Issue: No warnings for undefined variables
**Solution:** Check check_undefined_variables() method is called

### Issue: Duplicate variables created
**Solution:** Verify value-aware deduplication in find_variable_by_base_label_and_value()

---

## Success Criteria

[OK] **Phase 1 Complete:**
- All var() references accepted (not rejected)
- Color and size properties handle var() correctly
- No customCss fallback for var() values

[OK] **Phase 2 Complete:**
- Variables extracted from ALL selectors in <style> tags
- Direct css_variables parameter supported
- Both sources can be combined
- Warnings provided for undefined var() references
- Value-aware deduplication prevents duplicates
- Backward compatible (import_variables defaults to false)
- Order of operations correct (import BEFORE conversion)
