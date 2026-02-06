# Support Font Family - Google Fonts & Fallback Families

## Overview

Implement `Font_Family_Converter` to convert CSS `font-family` properties to Elementor v4 atomic widget format, with support for Google Fonts and fallback font chains.

## Implementation Details

### 1. Create Font Family Converter

**File:** `includes/converters/css/class-font-family-converter.php`

**Structure:**
- Extends `Property_Converter_Base`
- Supports `font-family` property
- Uses `String_Prop_Type` for output (matches v4 atomic widget schema)
- Parses CSS font-family values (handles quotes, commas, fallbacks)
- Checks font registration via `Fonts::get_font_type()`
- Preserves full fallback chain in prop value

**Key Methods:**

1. **`parse_font_family_value()`** - Parse CSS font-family string
   - Handle quoted fonts: `"Font Name"` or `'Font Name'`
   - Handle unquoted fonts: `Font Name`
   - Extract first font from fallback chain
   - Preserve full fallback chain

2. **`extract_primary_font()`** - Extract primary font name
   - Remove quotes
   - Normalize whitespace
   - Return first font in chain

3. **`normalize_font_name()`** - Normalize font name for matching
   - Remove quotes
   - Trim whitespace
   - Normalize multiple spaces to single space
   - Handle case sensitivity (Elementor fonts are case-sensitive)

4. **`is_registered_font()`** - Check if font is registered in Elementor
   - Use `\Elementor\Fonts::get_font_type($font_name)`
   - Returns font type if registered, false otherwise
   - Supports: `googlefonts`, `earlyaccess`, `system`, `local`, `custom`

5. **`is_css_keyword_to_skip()`** - Skip CSS keywords
   - `inherit`, `initial`, `unset`, `revert`
   - These are not actual font names

6. **`is_generic_font_family()`** - Check for generic families
   - `serif`, `sans-serif`, `monospace`, `cursive`, `fantasy`
   - These work without registration

**Conversion Logic:**
```php
protected function convert_value( string $property, $value ): ?array {
    // Skip CSS keywords
    if ( $this->is_css_keyword_to_skip( $value ) ) {
        return null;
    }
    
    // Parse font-family value
    $parsed = $this->parse_font_family_value( $value );
    
    // Extract primary font
    $primary_font = $this->extract_primary_font( $parsed );
    
    // Normalize for matching
    $normalized = $this->normalize_font_name( $primary_font );
    
    // Check registration (optional - for validation/logging)
    $font_type = \Elementor\Fonts::get_font_type( $normalized );
    
    // Store full fallback chain as-is
    // Elementor v4 will handle enqueuing automatically
    return String_Prop_Type::generate( $parsed['full_value'] );
}
```

### 2. Font Family Value Parser

**Parsing Strategy:**

Handle CSS font-family syntax:
- Single font: `font-family: Roboto;`
- Quoted font: `font-family: "Open Sans";`
- Fallback chain: `font-family: Roboto, Arial, sans-serif;`
- Mixed quotes: `font-family: "Helvetica Neue", Helvetica, Arial;`

**Parser Implementation:**
- Split by comma (respecting quotes)
- Trim each font name
- Remove quotes from individual fonts
- Preserve original format for output
- Handle edge cases (empty values, only commas, etc.)

### 3. Register Converter

**File:** `includes/plugin.php`

Add to `register_converters()` method:
```php
$this->registry->register( new Font_Family_Converter() );
```

**Location:** After other font-related converters (around line 198, after `Font_Style_Converter`)

### 4. Font Registration Check (Optional)

The converter can optionally validate font registration:
- Check if primary font is registered
- Log warnings for unregistered fonts (if needed)
- Still store the value even if unregistered (v4 accepts any string)

**Note:** Font enqueuing happens automatically in v4 via `useStylePropResolver` hook, so the converter doesn't need to enqueue fonts directly.

## Edge Cases

### 1. CSS Keywords
- `inherit`, `initial`, `unset`, `revert` → Skip (return null)

### 2. Generic Font Families
- `serif`, `sans-serif`, `monospace`, `cursive`, `fantasy` → Store as-is (work without registration)

### 3. CSS Variables
- `var(--custom-font)` → Handled by base class `Variable_Resolver` (if variable type is set)
- Font-family converter doesn't need variable support (fonts are strings, not variables)

### 4. Empty/Invalid Values
- Empty string → Skip (return null)
- Only whitespace → Skip (return null)
- Only commas → Skip (return null)

### 5. Quoted vs Unquoted
- `"Font Name"` and `Font Name` → Both normalized to same value for matching
- Preserve original format in output

### 6. Case Sensitivity
- Elementor font names are case-sensitive
- Match exactly as registered
- Don't change case of input value

## Testing Strategy

### Test Cases

1. **Google Fonts:**
   - `font-family: "Roboto";` → Should convert to String_Prop_Type with "Roboto"
   - `font-family: Roboto, sans-serif;` → Should preserve full chain

2. **System Fonts:**
   - `font-family: Arial;` → Should work (system font)
   - `font-family: "Helvetica Neue", Helvetica, Arial;` → Should preserve chain

3. **Fallback Chains:**
   - `font-family: PrimaryFont, FallbackFont, sans-serif;` → Should preserve all fonts
   - `font-family: "Font Name", serif;` → Should handle quotes correctly

4. **Edge Cases:**
   - `font-family: inherit;` → Should skip
   - `font-family: initial;` → Should skip
   - `font-family: ;` → Should skip (empty)
   - `font-family: var(--font);` → Handled by base class (if variable resolver configured)

5. **Quoted Fonts:**
   - `font-family: "Open Sans";` → Should extract "Open Sans"
   - `font-family: 'Roboto';` → Should extract "Roboto"
   - `font-family: "Font Name", Arial;` → Should handle mixed quotes

## Files to Modify

1. **Create:** `includes/converters/css/class-font-family-converter.php`
2. **Modify:** `includes/plugin.php` - Add converter registration

## Dependencies

- `Elementor\Fonts` class (for font registration check)
- `Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type` (for prop generation)
- `Property_Converter_Base` (base class)

## Notes

- Font enqueuing is handled automatically by Elementor v4's `useStylePropResolver` hook
- The converter only needs to parse and store the font-family value
- Unregistered fonts are still stored (v4 accepts any string value)
- Fallback chains are preserved in full (Elementor handles enqueuing of primary font)
