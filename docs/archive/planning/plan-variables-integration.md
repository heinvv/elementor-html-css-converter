# CSS Variables Integration - Other Endpoints

## Overview

Plan for integrating CSS variable support into existing REST endpoints.

**Status:** Ready for implementation
**Standalone endpoint:** [OK] Complete ([/import-variables](plan-variables-endpoint.md))
**Value-aware deduplication:** [OK] Implemented

---

## Integration Strategy

**Goal:** Allow existing endpoints to handle CSS with `var()` references

**Approach:** Keep it **basic** - pass through `var()` references as-is

---

## Confirmed Implementation Approach

### Variable Extraction from HTML

**Method:** Extract from `<style>` tags
- Extracts from **ALL selectors** (`:root`, `html`, `.class`, `#id`, media queries, etc.)
- Does NOT extract from inline `style=""` attributes
- Uses existing `Variable_Extractor` service with regex pattern

**Example:**
```html
<style>
  :root { --primary: #ff0000; }
  .dark { --primary: #0000ff; }
  #custom { --spacing: 20px; }
</style>
```
All 3 variables extracted: `primary`, `primary-1`, `spacing`

### Order of Operations

**Variables are imported BEFORE HTML conversion:**
1. Extract CSS from all `<style>` tags
2. Import variables to Elementor (if `import_variables: true`)
3. Check for undefined `var()` references
4. Convert HTML to widgets (with `var()` preserved)

### Undefined Variable Handling

**Behavior:** Include warnings in response (non-blocking)

**Response Format:**
```json
{
  "success": true,
  "widgets": [...],
  "variables": {...},
  "warnings": [
    "Variable '--unknown-color' used but not defined"
  ]
}
```

**Detection:** Scan HTML for `var()` references and check against:
- Variables imported from current request
- Variables already in Elementor repository

---

## Affected Endpoints

### 1. `/css-to-atomic`
**Current:** Converts CSS properties to atomic widget format
**Change:** Accept `var()` values instead of rejecting them

### 2. `/convert-html`
**Current:** Parses HTML and converts to Elementor widgets
**Change:** Extract and optionally import CSS variables from `<style>` tags

### 3. `/apply-styles-to-widget`
**Current:** Applies CSS to existing widget
**Change:** Accept `var()` in CSS properties

### 4. `/add-widget-to-post`
**Current:** Creates widget with styles
**Change:** Accept `var()` in CSS properties

### 5. `/create-post-with-widget`
**Current:** Creates post with widget
**Change:** Accept `var()` in CSS properties

---

## Implementation Plan

### Phase 1: Accept var() in Property Parsers (BASIC)

**Goal:** Stop rejecting `var()` values

#### Files to Update:

**1. Color Value Parser**
```php
// File: includes/parsers/class-color-value-parser.php
// Line: ~53-55

// CURRENT (rejects var):
if ( self::is_css_variable( $value ) ) {
    return false;  // [X] REJECTED
}

// NEW (accepts var):
if ( self::is_css_variable( $value ) ) {
    return true;  // [OK] ACCEPTED
}
```

**2. Size Value Parser**
```php
// File: includes/parsers/class-size-value-parser.php
// Similar change - accept var() values
```

#### Effect:
- `color: var(--primary-color)` → [OK] Accepted
- `font-size: var(--font-size)` → [OK] Accepted
- Values passed through unchanged to atomic widgets

**Testing:**
```bash
curl -X POST ".../css-to-atomic" \
  -d '{"cssString": "color: var(--primary-color);"}'

# Expected: Returns atomic widget with var() preserved
{
  "atomic": {
    "text_color": "var(--primary-color)"
  }
}
```

---

### Phase 2: Extract Variables from HTML

**Goal:** Automatically extract variables from `<style>` tags in HTML

**Extraction Behavior:**
- Extracts from **ALL selectors** within `<style>` tags (`:root`, `html`, `.class`, `#id`, media queries, etc.)
- Variables with same name but different values get incremental suffixes
- Import happens **BEFORE** HTML conversion begins

#### Add to HTML Converter

**File:** `includes/core/class-html-converter.php`

**New Parameter:** `import_variables` (boolean, default: `false`)

```php
public function convert_html_to_atomic_widgets( string $html, array $options = [] ): array {
    $warnings = [];
    $import_variables = $options['import_variables'] ?? false;

    if ( $import_variables ) {
        // STEP 1: Extract CSS from <style> tags (ALL selectors)
        $css = $this->extract_css_from_html( $html );

        // STEP 2: Import variables BEFORE conversion
        $import_result = $this->import_css_variables( $css, $options['update_mode'] ?? 'create_new' );

        // Track imported variables for undefined reference checking
        $imported_variables = $import_result['imported'] ?? [];

        // STEP 3: Check for undefined var() references
        $warnings = $this->check_undefined_variables( $html, $imported_variables );
    }

    // STEP 4: Continue with normal HTML conversion...
    $widgets = // ... conversion logic ...

    return [
        'widgets' => $widgets,
        'warnings' => $warnings,  // Include warnings in response
    ];
}

private function extract_css_from_html( string $html ): string {
    // Extract all <style> tag contents (includes ALL selectors: :root, html, .class, #id, etc.)
    preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $matches );
    return implode( "\n", $matches[1] ?? [] );
}

private function import_css_variables( string $css, string $update_mode ): array {
    // Use Variable_Extractor to find variables
    $extractor = new Variable_Extractor();
    $raw_vars = $extractor->extract_from_css( $css );

    if ( empty( $raw_vars ) ) {
        return ['imported' => []];
    }

    // Convert and store (reuse existing logic from Variables_Rest_API)
    $converted = Variable_Conversion_Service::convert_to_editor_variables( $raw_vars );
    $repository = new Variables_Repository( $active_kit );
    $store_result = // ... store variables using existing store_variables() logic

    return [
        'imported' => array_column( $converted, 'name' ),  // List of imported variable names
        'store_result' => $store_result,
    ];
}

private function check_undefined_variables( string $html, array $imported_variables ): array {
    $warnings = [];

    // Find all var() references in HTML
    preg_match_all( '/var\s*\(\s*(--[a-zA-Z0-9_-]+)/', $html, $matches );

    if ( ! empty( $matches[1] ) ) {
        foreach ( array_unique( $matches[1] ) as $var_name ) {
            // Check if variable was imported OR already exists in Elementor
            if ( ! in_array( $var_name, $imported_variables, true ) ) {
                $warnings[] = "Variable '{$var_name}' used but not defined";
            }
        }
    }

    return $warnings;
}
```

**Endpoint Parameters:**

**Option A: Direct CSS Variables Parameter (NEW)**
```json
{
  "html": "<div style='color: var(--primary-color)'>Text</div>",
  "css_variables": "--primary-color: #ff0000; --font-size: 16px;",
  "update_mode": "create_new"
}
```

**Option B: Extract from HTML Style Tags**
```json
{
  "html": "<style>--primary-color: #ff0000;</style><div>...</div>",
  "import_variables": true,
  "update_mode": "create_new"
}
```

**Option C: Both Sources Combined**
```json
{
  "html": "<style>--from-html: #00ff00;</style><div>Text</div>",
  "css_variables": "--from-param: #ff0000;",
  "import_variables": true,
  "update_mode": "create_new"
}
```
Result: Both `--from-param` and `--from-html` will be imported

**Testing:**
```bash
curl -X POST ".../convert-html" \
  -d '{
    "html": "<style>:root { --brand-color: #ff0000; } .dark { --brand-color: #0000ff; }</style><div style=\"color: var(--brand-color)\">Text</div>",
    "import_variables": true
  }'

# Expected Response:
{
  "success": true,
  "widgets": [...],
  "variables": {
    "brand-color": { "value": "#ff0000", "type": "color-hex" },
    "brand-color-1": { "value": "#0000ff", "type": "color-hex" }
  },
  "warnings": []  // No warnings - variable is defined
}

# Example with undefined variable:
curl -X POST ".../convert-html" \
  -d '{
    "html": "<div style=\"color: var(--undefined-color)\">Text</div>",
    "import_variables": true
  }'

# Expected Response:
{
  "success": true,
  "widgets": [...],
  "variables": {},
  "warnings": ["Variable '--undefined-color' used but not defined"]
}
```

---

## Edge Cases & Considerations

### 1. Variable Value Deduplication ([OK] IMPLEMENTED)

**Scenario:**
```css
/* Database has: */
primary-color: #ff0000 (red)
primary-color-1: #ffff00 (yellow)
primary-color-2: #800080 (purple)

/* User imports: */
--primary-color: #ffff00;
```

**Behavior:** [OK] Reuses `primary-color-1` (no duplicate created)

**Implementation:** `find_variable_by_base_label_and_value()` method

---

### 2. var() with Fallback Values

**Scenario:**
```css
color: var(--primary-color, #ff0000);
```

**Approach:** Pass through as-is (Elementor handles it)

**Decision:** Keep basic - no parsing needed

---

### 3. Case Sensitivity in Variable Names

**Scenario:**
```css
--Primary-Color: #ff0000;
--primary-color: #00ff00;
```

**Behavior:**
- Label matching is **case-insensitive**
- `Primary-Color` and `primary-color` treated as same label
- If values different → creates `primary-color-1`
- If values same → reuses existing

---

### 4. Very Long Variable Values

**Scenario:**
```css
--long-shadow: 0 0 1px #000, 0 0 2px #000, /* ... many values ... */;
```

**Handling:** No length limit in extraction

**Note:** Extremely long values may cause performance issues

---

### 5. calc() Expressions

**Scenario:**
```css
--spacing: calc(1rem + 2px);
```

**Behavior:** Not imported (unsupported type - doesn't match color/size pattern)

**Future:** Could add calc-expression convertor if needed

---

### 6. Variables in Inline Styles

**Scenario:**
```html
<div style="color: var(--primary-color)">Text</div>
```

**Phase 1:** `var()` passed through after implementation

**Phase 2:** NOT extracted (only from `<style>` tags)

---

## Implementation Checklist

### Phase 1: var() Pass-through
- [ ] Update Color_Value_Parser to accept var()
- [ ] Update Size_Value_Parser to accept var()
- [ ] Update relevant converters to handle var() values
- [ ] Test /css-to-atomic with var() references
- [ ] Test /apply-styles-to-widget with var()
- [ ] Test /add-widget-to-post with var()

### Phase 2: HTML Variable Extraction
- [ ] Add extract_css_from_html() to Html_Converter (extracts from ALL selectors)
- [ ] Add import_css_variables() method (imports BEFORE conversion)
- [ ] Add check_undefined_variables() method (warns about undefined var() references)
- [ ] Add import_variables parameter to /convert-html endpoint
- [ ] Update response format to include warnings array
- [ ] Test with :root selector variables
- [ ] Test with ALL selectors (.class, #id, etc.) - should extract from all
- [ ] Test with multiple <style> blocks
- [ ] Test with undefined var() references (should warn)
- [ ] Test with variables in inline styles (NOT extracted, only from <style> tags)

---

## Testing Scenarios

### Basic var() Pass-through

**Test 1: Color Variable**
```bash
POST /css-to-atomic
{
  "cssString": "color: var(--brand-color);"
}

Expected: {"atomic": {"text_color": "var(--brand-color)"}}
```

**Test 2: Size Variable**
```bash
POST /css-to-atomic
{
  "cssString": "font-size: var(--heading-size);"
}

Expected: {"atomic": {"typography_font_size": {"size": "var(--heading-size)"}}}
```

**Test 3: var() with Fallback**
```bash
POST /css-to-atomic
{
  "cssString": "color: var(--primary, #ff0000);"
}

Expected: {"atomic": {"text_color": "var(--primary, #ff0000)"}}
```

### HTML with Variables

**Test 4: Extract from Style Tag (All Selectors)**
```bash
POST /convert-html
{
  "html": "<style>:root { --color: #ff0000; }</style><p style=\"color: var(--color)\">Text</p>",
  "import_variables": true
}

Expected Response:
{
  "success": true,
  "widgets": [...],
  "variables": {
    "color": { "value": "#ff0000", "type": "color-hex" }
  },
  "warnings": []
}
```

**Test 5: Multiple Style Blocks**
```bash
POST /convert-html
{
  "html": "<style>--a: red;</style><div></div><style>--b: blue;</style>",
  "import_variables": true
}

Expected: Both variables extracted and stored
```

**Test 6: Extract from ALL Selectors**
```bash
POST /convert-html
{
  "html": "<style>:root { --color: #ff0000; } .dark { --color: #0000ff; } #custom { --spacing: 20px; }</style>",
  "import_variables": true
}

Expected Response:
{
  "success": true,
  "variables": {
    "color": { "value": "#ff0000", "type": "color-hex" },
    "color-1": { "value": "#0000ff", "type": "color-hex" },
    "spacing": { "value": "20px", "type": "size-length-viewport" }
  },
  "warnings": []
}

Note: ALL selectors are processed (:root, .dark, #custom)
```

**Test 7: Undefined Variable Warning**
```bash
POST /convert-html
{
  "html": "<style>:root { --defined: #ff0000; }</style><div style=\"color: var(--defined); background: var(--undefined);\">Text</div>",
  "import_variables": true
}

Expected Response:
{
  "success": true,
  "variables": {
    "defined": { "value": "#ff0000", "type": "color-hex" }
  },
  "warnings": [
    "Variable '--undefined' used but not defined"
  ]
}

Note: Conversion succeeds with warning (non-blocking)
```

---

## Files to Modify

### Phase 1 (Minimal Changes)

1. **includes/parsers/class-color-value-parser.php**
   - Line ~53: Change `return false` to `return true` for var()

2. **includes/parsers/class-size-value-parser.php**
   - Similar change for var() acceptance

3. **includes/converters/css/class-color-converter.php**
   - Handle var() passthrough (may already work)

### Phase 2 (HTML Variable Extraction)

4. **includes/core/class-html-converter.php**
   - Add `extract_css_from_html()` method (extracts from ALL selectors)
   - Add `import_css_variables()` method (imports BEFORE conversion)
   - Add `check_undefined_variables()` method (warns about undefined var())
   - Update `convert_html_to_atomic_widgets()` to:
     - Import variables first (if `import_variables: true`)
     - Check for undefined var() references
     - Include warnings in response

5. **includes/core/class-rest-api.php**
   - Add `import_variables` parameter to `/convert-html` route args
   - Update response schema to include `warnings` array

---

## Backward Compatibility

### Existing Behavior Preserved

**Without Changes:**
- `var()` currently rejected → goes to customCss
- Works but not ideal

**After Phase 1:**
- `var()` accepted → goes to proper atomic properties
- Better experience, no breaking changes

**After Phase 2:**
- New `import_variables` parameter (default: `false`)
- Existing calls work exactly as before
- New calls can opt-in to variable import

---

## Performance Considerations

### Variable Extraction

**Cost:** Regex match on CSS string

**Impact:** Minimal - simple pattern, only runs if `import_variables: true`

**Optimization:** Already optimized in Variable_Extractor

### Value-Aware Deduplication

**Cost:** Loads existing variables from repository, iterates to find match

**Impact:** Acceptable - only runs on import, not on every request

**Optimization:** Uses simple array iteration, no complex queries

---

## Future Enhancements (Not in This Plan)

1. **Variable Resolution**
   - Currently: Pass through var() as-is
   - Future: Optionally resolve var() to actual values

2. **calc() Expression Support**
   - Currently: Not supported
   - Future: Add calc-expression convertor

3. **Scoped Variables**
   - Currently: All variables stored globally
   - Future: Support selector-specific variables

4. **Variable Usage Tracking**
   - Currently: No tracking of where variables are used
   - Future: Track variable usage across widgets

---

## Summary

**Phase 1 (BASIC):**
- [OK] Simple: Change 2 lines of code
- [OK] No new features, just accept var()
- [OK] Immediate value for users
- [OK] Zero risk of breaking changes

**Phase 2 (HTML VARIABLE EXTRACTION):**
- Extract from ALL selectors in `<style>` tags
- Import BEFORE HTML conversion
- Warn about undefined `var()` references
- New opt-in `import_variables` parameter (default: `false`)
- Backward compatible
- Adds convenience for users

**Current Status:**
- Standalone endpoint: [OK] Complete
- Value-aware deduplication: [OK] Working
- var() pass-through: ⏳ Waiting for Phase 1
- HTML extraction: ⏳ Waiting for Phase 2

---

## Implementation Sequence

**Phase 1 (PRIORITY):** var() Pass-through
- Simple: Change 2-3 lines in parsers
- Immediate value: Stops rejecting `var()` references
- Zero risk: Backward compatible
- **Implement first** - foundation for Phase 2

**Phase 2:** HTML Variable Extraction
- Extract from ALL selectors in `<style>` tags
- Import variables BEFORE conversion
- Add undefined variable warnings
- Opt-in via `import_variables` parameter
- **Implement after** Phase 1 is tested and working

**Order Matters:** Phase 1 must work before Phase 2 makes sense (Phase 2 extracts variables, Phase 1 allows them to be used).
