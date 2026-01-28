# Task: Flex Property Merging

## Status: Completed ✓

## Solution Implemented

Updated `Flex_Converter` to expand the `flex` shorthand into separate properties (`flex-grow`, `flex-shrink`, `flex-basis`), following the same pattern as `Border_Converter`. This ensures:

1. `flex: 1 0 auto; flex-grow: 2;` → outputs `flex-grow: 2` (individual property overrides)
2. `flex-grow: 2; flex: 1 0 auto;` → outputs `flex-grow: 1` (shorthand wins when it comes later)
3. Individual flex properties without shorthand work as before
4. Flex shorthand without individual properties works as before

### Changes Made
- Modified `includes/converters/css/class-flex-converter.php`:
  - Removed `Flex_Prop_Type` dependency (not supported by Elementor)
  - Now returns associative array with `flex-grow`, `flex-shrink`, `flex-basis` keys
  - Uses `Number_Prop_Type` for grow/shrink and `Size_Prop_Type` for basis

---

## Original Problem

When CSS contains both the `flex` shorthand and individual flex properties, the individual properties should override the values from the shorthand. Currently, this doesn't work correctly.

### Example

**Input CSS:**
```css
flex: 1 0 auto;
flex-grow: 2;
flex-shrink: 0;
flex-basis: 200px;
```

**Expected Output:**
```css
flex: 2 0 200px;
/* or individual properties: */
flex-grow: 2;
flex-shrink: 0;
flex-basis: 200px;
```

**Actual Output:**
```css
flex: 1 0 auto;
/* Individual properties are ignored */
```

## Root Cause

The `flex` shorthand converter outputs a single `Flex_Prop_Type` with all three values. The individual converters (`Flex_Grow_Converter`, `Flex_Shrink_Converter`, `Flex_Basis_Converter`) output separate props. When CSS is processed, the shorthand creates a `flex` prop, but the individual properties either:
1. Don't override because they use different output property names
2. Are processed but overwritten by the shorthand

## Solution Options

### Option A: Merge at Css_Converter Level
Add logic in `Css_Converter::convert()` to detect when both `flex` shorthand and individual flex properties exist, then merge them into a single `flex` prop.

### Option B: Remove Individual Flex Converters
Remove `Flex_Grow_Converter`, `Flex_Shrink_Converter`, `Flex_Basis_Converter` and only support the `flex` shorthand. Users would need to use `flex: grow shrink basis` format.

### Option C: Expand Flex Shorthand (Recommended)
Similar to how css-converter handles this:
1. Process CSS properties in order
2. When `flex` shorthand is encountered, expand it to individual properties
3. When individual flex properties are encountered, they override the expanded values
4. At the end, combine back into a single `flex` prop

## Reference Implementation

From css-converter PR #32856, check:
- `modules/css-converter/services/css/processing/CSS_Shorthand_Expander.php`
- `modules/css-converter/convertors/css-properties/properties/flex-properties-mapper.php`

```bash
curl -s "https://raw.githubusercontent.com/elementor/elementor/hein/convert-css-to-widgets/modules/css-converter/services/css/processing/CSS_Shorthand_Expander.php"
```

## Files to Modify

- `includes/class-css-converter.php` - Add flex property merging logic
- Potentially create `includes/services/class-css-shorthand-expander.php`

## Acceptance Criteria

1. `flex: 1 0 auto; flex-grow: 2;` outputs `flex-grow: 2` (or merged flex prop)
2. `flex-grow: 2; flex: 1 0 auto;` outputs `flex: 1 0 auto` (shorthand wins when it comes later)
3. Individual flex properties without shorthand work as before
4. Flex shorthand without individual properties works as before

## Priority

Medium - This is an edge case that occurs when CSS contains both shorthand and individual properties for the same style.
