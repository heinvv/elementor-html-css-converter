# Plan: Complete css-converter Module Implementation

## Reference: Elementor css-converter Module

**IMPORTANT**: This is the authoritative reference for all implementations.

| Item | Value |
|------|-------|
| **GitHub PR** | #32856 |
| **Branch** | `hein/convert-css-to-widgets` |
| **API Command** | `gh api "repos/elementor/elementor/contents/modules/atomic-widgets/styles/transformers?ref=hein/convert-css-to-widgets"` |

To fetch any file from the reference implementation:
```bash
gh api "repos/elementor/elementor/contents/modules/atomic-widgets/styles/transformers/<filename>?ref=hein/convert-css-to-widgets" --jq '.content' | base64 -d
```

---

## Overview

This plan provides a complete mapping of all property mappers from the css-converter module in PR #32856. The goal is to implement **exact parity** with css-converter - no simplifications.

---

## COMPLETE PROPERTY MAPPER INVENTORY FROM PR #32856

### All CSS Property Mappers in css-converter:

| # | Mapper File | CSS Properties | Prop Type Output | Status |
|---|-------------|----------------|------------------|--------|
| 1 | `background-property-mapper.php` | `background`, `background-image` | `Background_Prop_Type` | ✅ IMPLEMENTED (gradients, images) |
| 2 | `background-color-property-mapper.php` | `background-color` | `Background_Prop_Type` (nested Color) | ✅ IMPLEMENTED |
| 3 | `color-property-mapper.php` | `color` | `Color_Prop_Type` | ✅ IMPLEMENTED |
| 4 | `padding-property-mapper.php` | `padding` (shorthand) | `Dimensions_Prop_Type` | ✅ IMPLEMENTED |
| 5 | `atomic-padding-property-mapper.php` | `padding-*` (all variants) | `Dimensions_Prop_Type` | ✅ IMPLEMENTED |
| 6 | `margin-property-mapper.php` | `margin`, `margin-*` (all variants) | `Dimensions_Prop_Type` | ✅ IMPLEMENTED |
| 7 | `width-property-mapper.php` | `width`, `min-width`, `max-width` | `Size_Prop_Type` | ✅ IMPLEMENTED (keywords, calc) |
| 8 | `height-property-mapper.php` | `height`, `min-height`, `max-height` | `Size_Prop_Type` | ✅ IMPLEMENTED (keywords, calc) |
| 9 | `font-size-property-mapper.php` | `font-size` | `Size_Prop_Type` | ✅ IMPLEMENTED |
| 10 | `display-property-mapper.php` | `display` | `String_Prop_Type` (enum) | ✅ IMPLEMENTED |
| 11 | `position-property-mapper.php` | `position` | `String_Prop_Type` (enum) | ✅ IMPLEMENTED |
| 12 | `flex-direction-property-mapper.php` | `flex-direction` | `String_Prop_Type` (enum) | ✅ IMPLEMENTED |
| 13 | `flex-properties-mapper.php` | `justify-content`, `align-items`, `align-content`, `align-self`, `flex-wrap`, `gap`, `row-gap`, `column-gap`, `flex`, `flex-grow`, `flex-shrink`, `flex-basis`, `order` | Various | ✅ IMPLEMENTED (all properties) |
| 14 | `border-property-mapper.php` | `border`, `border-top/right/bottom/left` | Multiple props | ✅ IMPLEMENTED (expands to border-width/style/color) |
| 15 | `border-radius-property-mapper.php` | `border-radius`, `border-*-*-radius` (all corners) | `Border_Radius_Prop_Type` | ✅ IMPLEMENTED |
| 16 | `border-width-property-mapper.php` | (via border shorthand) | `Size_Prop_Type` | ✅ IMPLEMENTED |
| 17 | `border-color-property-mapper.php` | (via border shorthand) | `Color_Prop_Type` | ✅ IMPLEMENTED |
| 18 | `border-style-property-mapper.php` | (via border shorthand) | `String_Prop_Type` | ✅ IMPLEMENTED |
| 19 | `box-shadow-property-mapper.php` | `box-shadow` | `Box_Shadow_Prop_Type` | ✅ IMPLEMENTED |
| 20 | `text-shadow-property-mapper.php` | `text-shadow` | Similar to box-shadow | ⚠️ NOT SUPPORTED (no Elementor prop type) |
| 21 | `opacity-property-mapper.php` | `opacity` | `Size_Prop_Type` (%) | ✅ IMPLEMENTED |
| 22 | `font-weight-property-mapper.php` | `font-weight` | `String_Prop_Type` | ✅ IMPLEMENTED |
| 23 | `font-style-property-mapper.php` | `font-style` | `String_Prop_Type` | ✅ IMPLEMENTED |
| 24 | `text-align-property-mapper.php` | `text-align` | `String_Prop_Type` (enum) | ✅ IMPLEMENTED |
| 25 | `line-height-property-mapper.php` | `line-height` | `Size_Prop_Type` | ✅ IMPLEMENTED |
| 26 | `letter-spacing-property-mapper.php` | `letter-spacing` | `Size_Prop_Type` | ✅ IMPLEMENTED |
| 27 | `word-spacing-property-mapper.php` | `word-spacing` | `Size_Prop_Type` | ✅ IMPLEMENTED |
| 28 | `text-transform-property-mapper.php` | `text-transform` | `String_Prop_Type` (enum) | ✅ IMPLEMENTED |
| 29 | `text-decoration-property-mapper.php` | `text-decoration` | `String_Prop_Type` | ✅ IMPLEMENTED |
| 30 | `transform-property-mapper.php` | `transform`, `transform-origin`, `perspective` | `Transform_Prop_Type` | ✅ IMPLEMENTED |
| 31 | `positioning-property-mapper.php` | `top`, `right`, `bottom`, `left`, `z-index`, `inset-*` | `Size_Prop_Type`, `Number_Prop_Type` | ✅ IMPLEMENTED |

**Summary:**
- ✅ Fully Implemented: 30 converters
- ⚠️ Not Supported: 1 property:
  - `text-shadow` - Elementor has no prop type

---

## PHASE 1: FIX EXISTING CONVERTERS ✅ COMPLETED

All Phase 1 fixes have been implemented:

### 1.1 Background Property Converter - NEEDS GRADIENT SUPPORT

**Current Implementation:** Only handles simple colors, rejects gradients/images.

**css-converter (`background-property-mapper.php`) supports:**
- Plain colors → `Background_Prop_Type` with `color` key
- Linear gradients → `Background_Overlay_Prop_Type` with `Background_Gradient_Overlay_Prop_Type`
- Radial gradients → Same overlay structure
- Background images → `background-image-overlay` with URL
- Composite shorthand → Color + image overlay combined

**Required code from css-converter:**
```php
// Gradient structure
$gradient_value = [
    'type' => String_Prop_Type::make()->generate('linear'),
    'angle' => Number_Prop_Type::make()->generate(90),
    'stops' => Gradient_Color_Stop_Prop_Type::make()->generate([
        Color_Stop_Prop_Type::make()->generate([
            'color' => Color_Prop_Type::make()->generate('#ff0000'),
            'offset' => Number_Prop_Type::make()->generate(0),
        ]),
        // ...more stops
    ]),
];
return Background_Gradient_Overlay_Prop_Type::make()->generate($gradient_value);
```

**File to modify:** `class-background-color-converter.php`

---

### 1.2 Gap Converter - NEEDS TWO-VALUE SUPPORT

**Current Implementation:** Rejects multi-value gap, only accepts single value.

**css-converter (`flex-properties-mapper.php`) supports:**
```php
private function map_gap_shorthand(string $value): ?array {
    $parts = preg_split('/\s+/', trim($value));
    $row_gap = $this->parse_size_value($parts[0]);
    $column_gap = isset($parts[1]) ? $this->parse_size_value($parts[1]) : $row_gap;

    return Layout_Direction_Prop_Type::make()->generate([
        'row' => Size_Prop_Type::make()->generate($row_gap),
        'column' => Size_Prop_Type::make()->generate($column_gap),
    ]);
}
```

**File to modify:** `class-gap-converter.php`

---

### 1.3 Width Converter - NEEDS KEYWORD SUPPORT

**Current Implementation:** Explicitly rejects all keywords, returns null.

**css-converter (`width-property-mapper.php`) supports:**
```php
$special_values = [
    'auto' => ['size' => 'auto', 'unit' => 'custom'],
    'fit-content' => ['size' => 'fit-content', 'unit' => 'custom'],
    'min-content' => ['size' => 'min-content', 'unit' => 'custom'],
    'max-content' => ['size' => 'max-content', 'unit' => 'custom'],
];
// calc() is also supported with unit='custom'
```

**File to modify:** `class-width-converter.php`

---

### 1.4 Height Converter - NEEDS KEYWORD SUPPORT

Same as Width Converter - needs `auto`, `fit-content`, `min-content`, `max-content` support.

**File to modify:** `class-height-converter.php`

---

### 1.5 Flex Properties - INCOMPLETE IMPLEMENTATION

**Current Implementation:** Only has basic justify-content, align-items, gap converters.

**css-converter (`flex-properties-mapper.php`) supports ALL of:**
- `justify-content` (enum: center, start, end, flex-start, flex-end, space-between, space-around, space-evenly, stretch)
- `align-items` (enum: normal, stretch, center, start, end, flex-start, flex-end, etc.)
- `align-content` (enum: center, start, end, space-between, space-around, space-evenly)
- `align-self` (enum: auto, normal, center, start, end, etc.)
- `flex-wrap` (enum: wrap, nowrap, wrap-reverse)
- `flex` shorthand (parses grow/shrink/basis)
- `flex-grow`, `flex-shrink` (Number_Prop_Type)
- `flex-basis` (Size_Prop_Type)
- `order` (Number_Prop_Type)

**Files to modify/create:**
- `class-justify-content-converter.php` - verify all enum values
- `class-align-items-converter.php` - verify all enum values
- Create `class-align-content-converter.php`
- Create `class-align-self-converter.php`
- Create `class-flex-wrap-converter.php`
- Create `class-flex-converter.php` (shorthand)
- Create `class-flex-grow-converter.php`
- Create `class-flex-shrink-converter.php`
- Create `class-flex-basis-converter.php`
- Create `class-order-converter.php`

---

## PHASE 2: HIGH PRIORITY NEW CONVERTERS ✅ COMPLETED

### 2.1 Border Radius Converter ✅ COMPLETED

**File created:** `class-border-radius-converter.php`

**Implementation:**
- Supports all individual corners: `border-top-left-radius`, etc.
- Supports logical corners: `border-start-start-radius`, etc.
- Uses `Border_Radius_Prop_Type` with logical properties: `start-start`, `start-end`, `end-start`, `end-end`
- Parses 1-4 value shorthand
- Rejects elliptical syntax (contains '/')

---

### 2.2 Border Converter ✅ COMPLETED

**Files created:**
- `class-border-converter.php` (shorthand expander)
- `class-border-width-converter.php` ✅
- `class-border-style-converter.php` ✅
- `class-border-color-converter.php` ✅

**Implementation:**
- Border shorthand is automatically expanded to multiple properties
- Input: `border: 1px solid red`
- Output: `border-width`, `border-style`, `border-color` as separate props

**How it works:**
- Border_Converter parses the shorthand and returns multiple properties
- Css_Converter detects multi-property returns and handles them appropriately
- Each expanded property uses the correct Elementor prop type

**Supported shorthands:**
- `border`, `border-top`, `border-right`, `border-bottom`, `border-left`
- `border-block-start`, `border-block-end`, `border-inline-start`, `border-inline-end`

---

### 2.3 Box Shadow Converter ✅ COMPLETED

**File created:** `class-box-shadow-converter.php`

**Implementation:**
- Parses: h-offset, v-offset, blur, spread, color, inset
- Supports multiple comma-separated shadows
- Uses `Box_Shadow_Prop_Type` with `Shadow_Prop_Type` items
- Handles inset keyword at start or end of shadow value

---

### 2.4 Opacity Converter ✅ COMPLETED

**File created:** `class-opacity-converter.php`

**Implementation:**
- Converts 0-1 to percentage (0.5 → 50%)
- Also handles percentage input (50% → 50%)
- Uses `Size_Prop_Type` with unit `%`

---

## PHASE 3: TYPOGRAPHY CONVERTERS ✅ COMPLETED

### 3.1 Font Weight Converter ✅ COMPLETED

**File created:** `class-font-weight-converter.php`

**Implementation:**
- Values: 100-900, normal, bold, bolder, lighter
- Maps keywords: thin→100, light→300, medium→500, semi-bold→600, etc.
- Normalizes numeric values outside 100-900 range
- Uses `String_Prop_Type`

---

### 3.2 Text Align Converter ✅ COMPLETED

**File created:** `class-text-align-converter.php`

**Implementation:**
- Values: start, center, end, justify
- Maps: left→start, right→end
- Uses `String_Prop_Type` (enum)

---

### 3.3 Line Height Converter ✅ COMPLETED

**File created:** `class-line-height-converter.php`

**Implementation:**
- Supports: numbers (unitless → converted to em), em, px, %, etc.
- 'normal' keyword → 1.2em
- Uses `Size_Prop_Type`

---

### 3.4 Letter Spacing Converter ✅ COMPLETED

**File created:** `class-letter-spacing-converter.php`

**Implementation:**
- Supports: px, em, etc.
- 'normal' keyword returns null
- Uses `Size_Prop_Type`

---

### 3.5 Word Spacing Converter ✅ COMPLETED

**File created:** `class-word-spacing-converter.php`

**Implementation:**
- Supports: px, em, etc.
- 'normal' keyword returns null
- Uses `Size_Prop_Type`

---

### 3.6 Text Transform Converter ✅ COMPLETED

**File created:** `class-text-transform-converter.php`

**Implementation:**
- Values: none, capitalize, uppercase, lowercase
- Case-insensitive matching
- Uses `String_Prop_Type` (enum)

---

### 3.7 Text Decoration Converter ✅ COMPLETED

**File created:** `class-text-decoration-converter.php`

**Implementation:**
- Values: none, underline, overline, line-through
- Extracts decoration line from shorthand (e.g., "underline solid red" → "underline")
- Uses `String_Prop_Type`

---

### 3.8 Font Style Converter ✅ COMPLETED

**File created:** `class-font-style-converter.php`

**Implementation:**
- Values: normal, italic, oblique
- Handles oblique with angle (extracts just "oblique")
- Uses `String_Prop_Type`

---

## PHASE 4: POSITIONING AND EFFECTS CONVERTERS ✅ MOSTLY COMPLETED

### 4.1 Positioning Converter ✅ COMPLETED

**File created:** `class-positioning-converter.php`

**Implementation:**
- Supports: `top`, `right`, `bottom`, `left`, `z-index`
- Supports logical: `inset-block-start`, `inset-block-end`, `inset-inline-start`, `inset-inline-end`
- Maps physical to logical: top→inset-block-start, right→inset-inline-end, etc.
- Handles 'auto' keyword
- `z-index` uses `Number_Prop_Type`

---

### 4.2 Text Shadow Converter ⚠️ NOT SUPPORTED

**File created:** `class-text-shadow-converter.php`

**Status:** Elementor's atomic widgets do NOT support `text-shadow`. There is no `Text_Shadow_Prop_Type` in Elementor's Style_Schema.

**Implementation:**
- Converter exists but always returns `null`
- Text-shadow will fall back to custom CSS

---

### 4.3 Transform Converter ✅ COMPLETED

**File created:** `class-transform-converter.php`

**Implementation:**
- Full parser for translate, scale, rotate, skew functions
- Uses `Transform_Prop_Type` with nested function types:
  - `Transform_Move_Prop_Type` (x, y, z) - translate, translateX/Y/Z, translate3d
  - `Transform_Scale_Prop_Type` (x, y, z) - scale, scaleX/Y/Z, scale3d
  - `Transform_Rotate_Prop_Type` (x, y, z) - rotate, rotateX/Y/Z, rotate3d
  - `Transform_Skew_Prop_Type` (x, y) - skew, skewX, skewY
- Supports `perspective` property
- `transform-origin` and `perspective-origin` return null (not fully supported)

---

## PHASE 5: CSS VARIABLE SUPPORT

Add CSS variable support (`var(--custom-property)`) to all converters:

1. Create `Css_Variable_Aware_Prop_Type` wrapper
2. Detect `var(--...)` syntax
3. Pass through to atomic format with variable reference

---

## FILES SUMMARY

### Files to Modify (Phase 1 Fixes):

| File | Changes |
|------|---------|
| `class-background-color-converter.php` | Add gradient parsing (linear, radial), image URL parsing |
| `class-gap-converter.php` | Add `Layout_Direction_Prop_Type` for 2-value support |
| `class-width-converter.php` | Add keyword handling (auto, fit-content, min-content, max-content) |
| `class-height-converter.php` | Add keyword handling |
| `class-justify-content-converter.php` | Verify all enum values match css-converter |
| `class-align-items-converter.php` | Verify all enum values match css-converter |
| `class-plugin.php` | Register all new converters |
| `elementor-html-css-converter.php` | Add require statements for new files |

### New Files to Create:

| Phase | File | Based On |
|-------|------|----------|
| 1 | `class-align-content-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-align-self-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-flex-wrap-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-flex-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-flex-grow-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-flex-shrink-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-flex-basis-converter.php` | `flex-properties-mapper.php` |
| 1 | `class-order-converter.php` | `flex-properties-mapper.php` |
| 2 | `class-border-radius-converter.php` | `border-radius-property-mapper.php` |
| 2 | `class-border-converter.php` | `border-property-mapper.php` |
| 2 | `class-border-width-converter.php` | `border-width-property-mapper.php` |
| 2 | `class-border-style-converter.php` | `border-style-property-mapper.php` |
| 2 | `class-border-color-converter.php` | `border-color-property-mapper.php` |
| 2 | `class-box-shadow-converter.php` | `box-shadow-property-mapper.php` |
| 2 | `class-opacity-converter.php` | `opacity-property-mapper.php` |
| 3 | `class-font-weight-converter.php` | `font-weight-property-mapper.php` |
| 3 | `class-text-align-converter.php` | `text-align-property-mapper.php` |
| 3 | `class-line-height-converter.php` | `line-height-property-mapper.php` |
| 3 | `class-letter-spacing-converter.php` | `letter-spacing-property-mapper.php` |
| 3 | `class-word-spacing-converter.php` | `word-spacing-property-mapper.php` |
| 3 | `class-text-transform-converter.php` | `text-transform-property-mapper.php` |
| 3 | `class-text-decoration-converter.php` | `text-decoration-property-mapper.php` |
| 3 | `class-font-style-converter.php` | `font-style-property-mapper.php` |
| 4 | `class-positioning-converter.php` | `positioning-property-mapper.php` |
| 4 | `class-text-shadow-converter.php` | `text-shadow-property-mapper.php` |
| 4 | `class-transform-converter.php` | `transform-property-mapper.php` |

---

## VERIFICATION

Test each converter with CSS inputs that match css-converter test cases:

```php
// Phase 1 Fixes
'background: linear-gradient(90deg, red 0%, blue 100%)'  // Gradient support
'gap: 10px 20px'                                          // Two-value gap
'width: auto'                                             // Keyword support
'width: fit-content'                                      // Keyword support

// Phase 2
'border-radius: 10px 20px 30px 40px'
'border: 1px solid red'
'box-shadow: 2px 4px 8px rgba(0,0,0,0.5), inset 0 0 10px white'
'opacity: 0.5'

// Phase 3 - Typography
'font-weight: 600'
'text-align: center'
'line-height: 1.5'
'letter-spacing: 2px'
'text-transform: uppercase'
'text-decoration: underline'

// Phase 4 - Positioning/Effects
'top: 10px'
'z-index: 100'
'transform: translateX(10px) rotate(45deg) scale(1.5)'
```

---

## Implementation Guidelines

### Follow Our Refined Approach (From Color_Converter)

All converters MUST follow the same patterns:

```php
class Example_Converter extends Property_Converter_Base {
    // 1. Constants for configuration
    private const SUPPORTED_PROPERTIES = ['property-name'];

    // 2. Required method from base
    protected function get_supported_properties_list(): array {
        return self::SUPPORTED_PROPERTIES;
    }

    // 3. Main conversion method
    public function convert( string $property, $value ): ?array {
        if ( ! $this->supports( $property ) ) {
            return null;
        }
        if ( ! $this->is_valid_value( $value ) ) {
            return null;
        }
        $normalized = $this->normalize_value( $value );
        if ( null === $normalized ) {
            return null;
        }
        return Prop_Type::generate( $normalized );
    }

    // 4. Self-documenting private methods
    private function is_valid_value( $value ): bool { ... }
}
```

### Design Principles

1. **Simple inheritance** - Max 2 levels (Converter → Base)
2. **Self-documenting methods** - Names describe what they check
3. **Explicit constants** - No magic strings/numbers in code
4. **Early returns** - Fail fast with null for invalid input
5. **Single responsibility** - Each private method does one thing
6. **Direct prop type usage** - Use Elementor's prop types directly
7. **Exact parity with css-converter** - NO simplifications

### Elementor Prop Types Reference

| Value Type | Elementor Prop Type |
|------------|---------------------|
| Color | `Color_Prop_Type::generate($color)` |
| Size | `Size_Prop_Type::generate(['size' => $n, 'unit' => $u])` |
| String | `String_Prop_Type::generate($value)` |
| Number | `Number_Prop_Type::generate($value)` |
| Dimensions | `Dimensions_Prop_Type::generate($dimensions)` |
| Border Radius | `Border_Radius_Prop_Type::generate($corners)` |
| Box Shadow | `Box_Shadow_Prop_Type::generate($shadow)` |
| Background | `Background_Prop_Type::generate($background)` |
| Layout Direction | `Layout_Direction_Prop_Type::generate(['row' => ..., 'column' => ...])` |
| Transform | `Transform_Prop_Type::generate($transform)` |

---

## Frontend Rendering Issue (Reference)

### Problem
Styles are being saved to the database correctly, but they don't render on the frontend.

### Root Cause
The HTML output shows `<h2 class="e-heading-base">` instead of `<h2 class="e-abc1234-1234567 e-heading-base">`.

### Fix Applied
- Ensure `settings.classes` has correct format: `{'$$type': 'classes', 'value': ['e-xxx-yyy']}`
- Ensure `styles` has the style definition with matching ID
- Ensure `editor_settings` is present in widget data

### Verification
1. Apply styles via API endpoint
2. Check `_elementor_data` in database for correct structure
3. Load frontend page and inspect HTML for class attribute
4. Check browser DevTools for CSS rules matching the style class
