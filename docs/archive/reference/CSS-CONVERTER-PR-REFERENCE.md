# CSS Converter Module Reference (PR #32856)

## IMPORTANT: Read This First

This document contains critical implementation details from Elementor's css-converter module in PR #32856.
**Always study this file before making changes to ensure parity with the official implementation.**

## GitHub PR Information

- **PR Number**: #32856
- **Branch**: `hein/convert-css-to-widgets`
- **Repository**: `elementor/elementor`
- **PR URL**: https://github.com/elementor/elementor/pull/32856

## How to Fetch Latest Code (using GITHUB_TOKEN)

```bash
# List all files in the PR
curl -s -H "Authorization: token $GITHUB_TOKEN" \
  "https://api.github.com/repos/elementor/elementor/pulls/32856/files?per_page=100&page=2" | jq -r '.[].filename'

# Fetch a specific file from the branch
curl -s "https://raw.githubusercontent.com/elementor/elementor/hein/convert-css-to-widgets/modules/css-converter/convertors/css-properties/properties/background-property-mapper.php"
```

## Key Files in css-converter Module

### Property Mappers (modules/css-converter/convertors/css-properties/properties/)

| File | CSS Properties | Output Prop Type |
|------|----------------|------------------|
| `background-property-mapper.php` | `background`, `background-image` | `Background_Prop_Type` |
| `background-color-property-mapper.php` | `background-color` | `Background_Prop_Type` |
| `color-property-mapper.php` | `color` | `Color_Prop_Type` |
| `padding-property-mapper.php` | `padding` (shorthand) | `Dimensions_Prop_Type` |
| `margin-property-mapper.php` | `margin`, `margin-*` | `Dimensions_Prop_Type` |
| `width-property-mapper.php` | `width`, `min-width`, `max-width` | `Size_Prop_Type` |
| `height-property-mapper.php` | `height`, `min-height`, `max-height` | `Size_Prop_Type` |
| `font-size-property-mapper.php` | `font-size` | `Size_Prop_Type` |
| `display-property-mapper.php` | `display` | `String_Prop_Type` (enum) |
| `position-property-mapper.php` | `position` | `String_Prop_Type` (enum) |
| `flex-direction-property-mapper.php` | `flex-direction` | `String_Prop_Type` (enum) |
| `flex-properties-mapper.php` | `justify-content`, `align-items`, `gap`, `flex`, etc. | Various |
| `border-property-mapper.php` | `border`, `border-*` | Multiple props |
| `border-radius-property-mapper.php` | `border-radius`, corners | `Border_Radius_Prop_Type` |
| `box-shadow-property-mapper.php` | `box-shadow` | `Box_Shadow_Prop_Type` |
| `opacity-property-mapper.php` | `opacity` | `Size_Prop_Type` (%) |
| `font-weight-property-mapper.php` | `font-weight` | `String_Prop_Type` |
| `text-align-property-mapper.php` | `text-align` | `String_Prop_Type` (enum) |
| `line-height-property-mapper.php` | `line-height` | `Size_Prop_Type` |
| `letter-spacing-property-mapper.php` | `letter-spacing` | `Size_Prop_Type` |
| `text-transform-property-mapper.php` | `text-transform` | `String_Prop_Type` (enum) |
| `text-decoration-property-mapper.php` | `text-decoration` | `String_Prop_Type` |
| `transform-property-mapper.php` | `transform` | `Transform_Prop_Type` |
| `positioning-property-mapper.php` | `top`, `right`, `bottom`, `left`, `z-index` | Various |

### Widget Services (modules/css-converter/services/atomic-widgets/)

| File | Purpose |
|------|---------|
| `atomic-widget-json-creator.php` | Creates widget JSON structure |
| `atomic-widget-settings-preparer.php` | Prepares widget settings |
| `atomic-widget-class-generator.php` | Generates unique class IDs |
| `css-to-atomic-props-converter.php` | Converts CSS to atomic props |
| `atomic-widgets-orchestrator.php` | Orchestrates the conversion |

---

## Critical Implementation Details

### 1. Background Gradient Structure

**Linear Gradient:**
```php
return [
    'type'  => String_Prop_Type::generate('linear'),
    'angle' => Number_Prop_Type::generate($angle),
    'stops' => Gradient_Color_Stop_Prop_Type::generate($stops),
];
```

**Radial Gradient (MUST include `positions`):**
```php
$gradient_value = [
    'type' => String_Prop_Type::generate('radial'),
    'stops' => $this->extract_gradient_stops_wrapped($parts),
];

// ALWAYS add positions for radial gradients
$position = $this->extract_radial_position($parts[0]);
$gradient_value['positions'] = String_Prop_Type::generate($position);

// Also set angle to 180 for radial (matches editor)
$gradient_value['angle'] = Number_Prop_Type::generate(180);
```

**Gradient Stop Structure:**
```php
Color_Stop_Prop_Type::generate([
    'color'  => Color_Prop_Type::generate($color),
    'offset' => Number_Prop_Type::generate($offset), // 0-100 (percentage)
]);
```

### 2. Widget Style Structure

Each widget should have **ONE "local" style**, not multiple:

```json
{
  "id": "widget-id",
  "settings": {
    "classes": {
      "$$type": "classes",
      "value": ["e-widgetid-styleid"]
    }
  },
  "styles": {
    "e-widgetid-styleid": {
      "id": "e-widgetid-styleid",
      "type": "class",
      "label": "local",
      "variants": [{
        "meta": { "breakpoint": "desktop", "state": null },
        "props": { /* atomic props */ },
        "custom_css": null
      }]
    }
  }
}
```

**When applying styles multiple times:**
- Check if "local" style exists
- If exists: MERGE props into existing style
- If not: CREATE new "local" style

### 3. Dimensions (padding/margin) Structure

Uses logical properties (not physical):
```json
{
  "$$type": "dimensions",
  "value": {
    "block-start": { "$$type": "size", "value": { "size": 10, "unit": "px" } },
    "inline-end": { "$$type": "size", "value": { "size": 20, "unit": "px" } },
    "block-end": { "$$type": "size", "value": { "size": 10, "unit": "px" } },
    "inline-start": { "$$type": "size", "value": { "size": 20, "unit": "px" } }
  }
}
```

Mapping:
- `padding-top` / `margin-top` → `block-start`
- `padding-right` / `margin-right` → `inline-end`
- `padding-bottom` / `margin-bottom` → `block-end`
- `padding-left` / `margin-left` → `inline-start`

### 4. Size with Keywords

Size values can have special keywords:
```php
$special_values = [
    'auto' => ['size' => 'auto', 'unit' => 'custom'],
    'fit-content' => ['size' => 'fit-content', 'unit' => 'custom'],
    'min-content' => ['size' => 'min-content', 'unit' => 'custom'],
    'max-content' => ['size' => 'max-content', 'unit' => 'custom'],
];
```

### 5. Gap Property (Two-Value Support)

```php
// "gap: 10px 20px" → row-gap: 10px, column-gap: 20px
Layout_Direction_Prop_Type::generate([
    'row' => Size_Prop_Type::generate($row_gap),
    'column' => Size_Prop_Type::generate($column_gap),
]);
```

### 6. Class ID Format

Pattern: `e-{widget_id}-{7_char_hex}`
Example: `e-abc1234-def5678`

```php
$unique_id = substr(bin2hex(random_bytes(4)), 0, 7);
return "e-{$widget_id}-{$unique_id}";
```

---

## Prop Type Generation Methods

Always use the Elementor prop type classes:

```php
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Gradient_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Gradient_Color_Stop_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Stop_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

// Usage:
Color_Prop_Type::generate('#ff0000');
Size_Prop_Type::generate(['size' => 10, 'unit' => 'px']);
String_Prop_Type::generate('center');
Number_Prop_Type::generate(180);
```

---

## Known Issues & Solutions

### Issue: Multiple "local" styles created
**Solution**: Check for existing "local" style and merge props instead of creating new style.

### Issue: Radial gradient not rendering
**Solution**: Always include `positions` field with format "x y" (e.g., "center center").

### Issue: Frontend styles not applied
**Solution**: Ensure `settings.classes.value` array contains the style ID that matches `styles[id]`.

---

## Verification Checklist

When implementing a new converter, verify:

1. [ ] Output matches css-converter's structure exactly
2. [ ] Uses correct Elementor prop type classes
3. [ ] Handles all variations (shorthand, individual properties)
4. [ ] Maps to correct output property name
5. [ ] Properly handles edge cases (inherit, initial, etc.)

---

## Last Updated

Date: 2026-01-27
Updated by: Claude Code
Reason: Initial creation with gradient and local style fixes
