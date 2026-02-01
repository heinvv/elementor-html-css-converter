# Plan: Global Classes Import with HTML Conversion

## Overview

Enhance the `/convert-html` endpoint to automatically create Elementor Global Classes from `.class` selectors in `<style>` tags, while also creating widgets from HTML elements.

**Example Input:**
```html
<style>
:root { --primary: #007bff; }
.my-class { color: red; padding: 10px; }
#my-id { font-size: 24px; }
</style>
<div class="my-class" id="my-id">content</div>
```

**Expected Behavior:**
1. `:root` variables → Import to Elementor Variables (already implemented)
2. `.my-class` → Create Global Class + apply to widget
3. `#my-id` → Apply inline styles to widget (already implemented)
4. `<div>` → Create e-div-block widget

---

## Reference: How elementor-css/widget-converter Handles This

### Endpoint: `POST /wp-json/elementor/v2/widget-converter`

**Key Flow (from `atomic-widgets-route.php`):**
1. Parse HTML with DOMDocument
2. Extract `<style>` tags and external CSS URLs
3. Process CSS through `Unified_Css_Processor`
4. Create widgets via `Widget_Creation_Orchestrator`
5. Apply styles via class matching

### Key Services Used:
| Service | Purpose |
|---------|---------|
| `Unified_Css_Processor` | Parses CSS and converts to atomic props |
| `Selector_Matcher_Engine` | Matches CSS selectors to HTML elements |
| `Global_Classes_Registration_Service` | Stores classes in repository |
| `Widget_Class_Processor` | Applies class styles to widgets |

---

## ALL Use Cases from css-converter (Mapped)

### A. SUPPORTED Scenarios (to implement)

| Scenario | Example | Status |
|----------|---------|--------|
| Simple class selector | `.btn { color: red; }` | TO IMPLEMENT |
| Class on element | `<div class="btn">` | TO IMPLEMENT |
| Multiple classes on element | `class="btn primary"` | TO IMPLEMENT |
| Class + ID on same element | `class="btn" id="hero"` | TO IMPLEMENT (class as global, ID as inline) |
| Same class multiple rules | `.btn { color: red; } .btn { padding: 10px; }` | TO IMPLEMENT (merge) |

### B. SKIPPED Scenarios (MVP - basic functionality only)

| Scenario | Example | Reason |
|----------|---------|--------|
| Compound selectors | `.btn.primary` | Complex - skip |
| Nested/descendant | `.parent .child` | Complex - skip |
| Child combinator | `.parent > .child` | Complex - skip |
| Sibling selectors | `.a + .b`, `.a ~ .b` | Complex - skip |
| Pseudo-classes | `.btn:hover` | State handling - skip |
| Pseudo-elements | `.btn::before` | Not supported - skip |
| Element+class | `div.btn` | Complex - skip |
| Attribute selectors | `[type="text"]` | Complex - skip |
| Media queries | `@media (max-width: 768px)` | Responsive - skip |
| Keyframes | `@keyframes fade` | Animation - skip |

### C. PASS-THROUGH Scenarios (already handled)

| Scenario | Current Handling |
|----------|------------------|
| `:root` variables | Variable import (existing) |
| `#id` selectors | ID-based inline styles (existing) |
| CSS variables `var()` | Variable resolver (existing) |

---

## Edge Cases & Handling

| Edge Case | Handling |
|-----------|----------|
| **Duplicate class names (same styles)** | Reuse existing global class silently |
| **Duplicate class names (different styles)** | Create with suffix `-2`, `-3` |
| **100 global class limit reached** | Return partial success with overflow array |
| **Label > 50 characters** | Truncate class name |
| **Unsupported CSS property** | Store in `custom_css` field |
| **Empty class definition** | Skip silently |

| **Class not used in HTML** | Don't create.
| **HTML element without matching class style** | Create widget without global class ref |
| **`!important` flag** | Strip from value |
| **CSS comments** | Remove before parsing |
| **Invalid CSS syntax** | Skip rule, continue processing, but leave comment in results. |

---

## Design Decisions (Confirmed)

| Decision | Choice |
|----------|--------|
| **Class creation scope** | Used only - create global class only if class appears in HTML element |
| **Class + ID handling** | Merge both - widget gets global class ref + local style from ID |
| **Multiple classes** | Multiple refs - widget references multiple global classes |
| **Error handling** | Partial success - continue, skip failed classes, return warnings |
| **Parameter format** | Boolean `import_classes: true/false` |
| **Class name format** | Preserve original - `.btn-primary` → label `btn-primary` |

---

## Proposed Implementation Architecture

### New Parameter for `/convert-html`

```php
'import_classes' => [
    'type' => 'boolean',  // or 'string' for mode
    'default' => false,
    'description' => 'Create global classes from .class selectors'
]
```

### Processing Flow

```
HTML Input with <style> tags
    ↓
[1] Extract CSS from <style> tags (existing)
    ↓
[2] Extract CSS variables from :root (existing)
    ↓
[3] NEW: Extract .class selectors
    │   └── Use Class_Extractor (from import-classes feature)
    │   └── Filter: simple classes only, skip complex selectors
    ↓
[4] NEW: Convert class styles to atomic props
    │   └── Use Class_Conversion_Service
    ↓
[5] NEW: Register as Global Classes
    │   └── Use Class_Registration_Service
    │   └── Handle duplicates, limits, etc.
    ↓
[6] Parse HTML elements (existing)
    ↓
[7] NEW: Match HTML class="" to registered global classes
    │   └── Add class IDs to widget['settings']['classes']['value']
    ↓
[8] Apply #id styles as local styles (existing)
    ↓
[9] Create widgets with global class refs + local ID styles
    ↓
Response with widgets + imported_classes array
```

### Services to Reuse

| Service | Location | Purpose |
|---------|----------|---------|
| `Class_Extractor` | `converters/classes/` | Extract .class selectors |
| `Class_Conversion_Service` | `converters/classes/` | Convert to atomic |
| `Class_Registration_Service` | `converters/classes/` | Store in Global Classes |
| `Css_Converter` | `converters/core/` | Property conversion |
| `Variable_Extractor` | `converters/variables/` | Variable import |

### Files to Modify

| File | Changes |
|------|---------|
| `class-rest-api.php` | Add `import_classes` parameter |
| `class-html-converter.php` | Integrate class extraction and registration |
| `class-atomic-data-parser.php` | Match HTML classes to global class IDs |
| `class-widget-styles-integrator.php` | Support global class refs alongside local styles |

### New Helper Methods Needed

```php
// In Html_Converter or new service
extract_class_selectors_from_css( string $css ): array
match_html_classes_to_global_classes( array $element_classes, array $registered_classes ): array
```

---

## Response Structure Enhancement

```json
{
  "success": true,
  "widgets": [...],
  "imported_variables": ["--primary", "--secondary"],
  "imported_classes": {
    "btn": {
      "label": "btn",
      "elementor_id": "g-123",
      "status": "created"
    },
    "primary": {
      "label": "primary",
      "elementor_id": "g-124",
      "status": "reused"
    }
  },
  "class_statistics": {
    "detected": 5,
    "created": 3,
    "reused": 1,
    "skipped": 1
  },
  "skipped_classes": [
    {
      "selector": ".parent .child",
      "reason": "nested selector not supported"
    }
  ],
  "warnings": []
}
```

---

## Test Cases

### Basic Test
```html
<style>
.btn { color: blue; padding: 10px; }
</style>
<button class="btn">Click me</button>
```
Expected: Global class `btn` created, widget has `classes: { value: ['btn-id'] }`

### Multiple Classes Test
```html
<style>
.btn { padding: 10px; }
.primary { color: blue; }
</style>
<button class="btn primary">Click me</button>
```
Expected: Two global classes, widget references both

### Class + ID Test
```html
<style>
.btn { padding: 10px; }
#hero { font-size: 24px; }
</style>
<button class="btn" id="hero">Click me</button>
```
Expected: Global class for `.btn`, local style for `#hero`, widget has both

### Skip Complex Test
```html
<style>
.btn { color: blue; }
.btn:hover { color: red; }
.parent .child { padding: 5px; }
</style>
<button class="btn">Click me</button>
```
Expected: Only `.btn` imported, `:hover` and nested skipped with warnings

---

## Implementation Summary

### Key Behaviors:
1. **Used-only import**: Only classes found in HTML `class=""` get global classes
2. **Merge class + ID**: Both applied to widget (global class ref + local ID style)
3. **Multiple global refs**: `class="btn primary"` → `classes.value = ['btn-id', 'primary-id']`
4. **Partial success**: Continue on failures, return warnings
5. **Simple classes only**: Skip compound, nested, pseudo-classes/elements, media queries
