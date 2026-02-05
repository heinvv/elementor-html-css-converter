# Support Font Family - Advanced Features

## Overview

Extend font-family converter with advanced features: Adobe Fonts support, embedded font detection, improved font name normalization, fuzzy matching, and better handling of unregistered fonts.

## Implementation Details

### 1. Adobe Fonts Support

**Requirement:** Fonts must be registered in Elementor first (via Assets Manager or filters)

**Implementation:**

**File:** `includes/converters/css/class-font-family-converter.php`

**Enhancements:**

1. **Custom Font Detection:**
   - Check for `custom` font type via `Fonts::get_font_type()`
   - Custom fonts include Adobe Fonts (TypeKit) if registered
   - Handle case-sensitive matching for custom fonts

2. **Font Registration Helper:**
   - Add method to check if font is custom/Adobe font
   - Log helpful message if Adobe font is detected but not registered
   - Store font as-is even if not registered (will work if registered later)

**Code Addition:**
```php
private function is_custom_font( string $font_name ): bool {
    $font_type = \Elementor\Fonts::get_font_type( $font_name );
    return 'custom' === $font_type || 'local' === $font_type;
}

private function check_adobe_font_registration( string $font_name ): void {
    // Optional: Log if Adobe font detected but not registered
    // This helps users understand why font might not work
}
```

### 2. Embedded Font Detection

**Challenge:** Fonts defined only in @font-face CSS won't be automatically detected

**Implementation Strategy:**

**Option A: CSS Parsing (Basic)**
- Parse @font-face declarations from provided CSS
- Extract font-family names from @font-face rules
- Match against font-family values in styles
- Store matched fonts with special handling

**Option B: Font Registration Detection (Advanced)**
- Check if font is registered in Elementor (custom/local)
- If not registered, check if font appears in @font-face declarations
- Provide warnings/suggestions for unregistered embedded fonts

**File:** `includes/converters/css/class-font-family-converter.php`

**New Methods:**

1. **`detect_embedded_fonts()`** - Parse @font-face declarations
   - Extract font-family from @font-face rules
   - Build map of embedded fonts
   - Match against font-family values

2. **`is_embedded_font()`** - Check if font is defined in @font-face
   - Check against parsed @font-face declarations
   - Return true if font is embedded but not registered

**Limitation:** Requires access to full CSS context (not just individual property values)

**Alternative Approach:**
- Store unregistered fonts as-is
- Add to custom CSS if needed
- Document that fonts must be registered in Elementor for full support

### 3. Font Name Normalization & Fuzzy Matching

**Problem:** Font names may vary in format (spaces, quotes, case)

**Implementation:**

**File:** `includes/converters/css/class-font-family-converter.php`

**Enhanced Normalization:**

1. **Whitespace Normalization:**
   - Multiple spaces → single space
   - Trim leading/trailing whitespace
   - Handle tabs, newlines

2. **Quote Handling:**
   - Remove quotes for matching
   - Preserve quotes in output if needed
   - Handle both single and double quotes

3. **Case Normalization (Optional):**
   - For matching: case-insensitive comparison
   - For output: preserve original case
   - Elementor fonts are case-sensitive, so preserve case

4. **Fuzzy Matching:**
   - "Open Sans" vs "OpenSans" → Try both variations
   - "Roboto" vs "Roboto Regular" → Match base name
   - Handle common variations

**Code Enhancement:**
```php
private function normalize_font_name_for_matching( string $font_name ): string {
    $normalized = trim( $font_name );
    $normalized = preg_replace( '/["\']/', '', $normalized );
    $normalized = preg_replace( '/\s+/', ' ', $normalized );
    return $normalized;
}

private function find_font_variations( string $font_name ): array {
    $variations = [ $font_name ];
    
    // Try without spaces
    $variations[] = str_replace( ' ', '', $font_name );
    
    // Try with different quote styles
    $variations[] = '"' . $font_name . '"';
    $variations[] = "'" . $font_name . "'";
    
    return array_unique( $variations );
}

private function fuzzy_match_font( string $font_name ): ?string {
    $normalized = $this->normalize_font_name_for_matching( $font_name );
    $variations = $this->find_font_variations( $normalized );
    
    foreach ( $variations as $variation ) {
        $font_type = \Elementor\Fonts::get_font_type( $variation );
        if ( false !== $font_type ) {
            return $variation;
        }
    }
    
    return null;
}
```

### 4. Unregistered Font Handling

**Current Behavior:** Store as-is (works but font won't be enqueued)

**Enhanced Behavior:**

**Option A: Custom CSS Fallback**
- If font is not registered, add to custom CSS
- Preserve font-family in custom CSS block
- Document limitation

**Option B: Font Registration Suggestions**
- Detect unregistered fonts
- Provide helpful error messages
- Suggest registering font in Elementor

**Option C: Hybrid Approach**
- Store in prop value (works if registered later)
- Add to custom CSS as fallback
- Log warning for unregistered fonts

**Implementation:**

**File:** `includes/converters/css/class-font-family-converter.php`

**Enhancement:**
```php
protected function convert_value( string $property, $value ): ?array {
    // ... existing parsing logic ...
    
    $primary_font = $this->extract_primary_font( $parsed );
    $normalized = $this->normalize_font_name( $primary_font );
    
    // Try fuzzy matching
    $matched_font = $this->fuzzy_match_font( $normalized );
    
    if ( null === $matched_font ) {
        // Font not registered - could add to custom CSS
        // For now, store as-is (will work if registered later)
        $this->handle_unregistered_font( $normalized );
    }
    
    return String_Prop_Type::generate( $parsed['full_value'] );
}

private function handle_unregistered_font( string $font_name ): void {
    // Optional: Log warning
    // Optional: Add to custom CSS
    // For now: Just store as-is
}
```

### 5. Integration with Custom CSS

**Enhancement:** Add unregistered fonts to custom CSS block

**File:** `includes/converters/css/class-css-converter.php`

**Modification:**
- Track unregistered fonts during conversion
- Add font-family declarations to custom CSS if font is not registered
- Preserve @font-face declarations in custom CSS

**Note:** This requires coordination between converter and CSS converter

### 6. Font Registration Helper

**Feature:** Provide utilities to help users register fonts

**File:** `includes/converters/css/class-font-registration-helper.php` (new)

**Purpose:**
- Detect unregistered fonts
- Provide registration instructions
- Check if Elementor Pro Assets Manager is available
- Suggest font registration methods

**Methods:**
- `detect_unregistered_fonts( array $font_families ): array`
- `get_registration_suggestions( string $font_name ): array`
- `is_assets_manager_available(): bool`

## Files to Modify/Create

1. **Modify:** `includes/converters/css/class-font-family-converter.php`
   - Add Adobe Fonts detection
   - Add embedded font detection
   - Add fuzzy matching
   - Add unregistered font handling

2. **Create:** `includes/converters/css/class-font-registration-helper.php` (optional)
   - Helper class for font registration utilities

3. **Modify:** `includes/converters/css/class-css-converter.php` (optional)
   - Integrate unregistered font handling with custom CSS

## Testing Strategy

### Test Cases

1. **Adobe Fonts:**
   - `font-family: "Adobe Font Name";` → If registered, should work
   - `font-family: "Unregistered Adobe Font";` → Should store as-is, log warning

2. **Embedded Fonts:**
   - CSS with @font-face → Should detect embedded fonts
   - Match font-family to @font-face → Should recognize embedded font

3. **Fuzzy Matching:**
   - `font-family: "Open Sans";` → Should match "Open Sans" or "OpenSans"
   - `font-family: Roboto;` → Should match "Roboto" (case variations)

4. **Unregistered Fonts:**
   - `font-family: "Unknown Font";` → Should store as-is
   - Check if added to custom CSS (if implemented)
   - Check if warning is logged

5. **Font Name Variations:**
   - `font-family: "Font Name";` → Should match "Font Name" or "FontName"
   - `font-family: 'Font Name';` → Should handle single quotes
   - `font-family: Font  Name;` → Should normalize multiple spaces

## Dependencies

- Elementor Pro Assets Manager (for Adobe Fonts)
- Access to full CSS context (for @font-face parsing)
- Font registration system

## Limitations

1. **Adobe Fonts:** Must be registered in Elementor first
2. **Embedded Fonts:** Requires full CSS context (may not be available)
3. **Font Registration:** Cannot auto-register fonts (must be done manually)
4. **Custom CSS:** May need coordination with CSS converter

## Future Enhancements

1. **Auto-Registration:** Attempt to register fonts automatically (if possible)
2. **Font Detection Service:** Use external service to detect font sources
3. **Font Preview:** Show font preview in converter output
4. **Font Validation:** Validate font names against known font databases
5. **Batch Processing:** Process multiple fonts efficiently

## Notes

- Advanced features build on basic font-family converter
- Some features require Elementor Pro
- Embedded font detection may need architectural changes
- Fuzzy matching improves user experience but adds complexity
- Unregistered font handling provides graceful degradation
