# Claude Rules for elementor-html-css-converter

## CRITICAL: Reference PR for Implementation Parity

This plugin must maintain **exact parity** with Elementor's official `css-converter` module.

### Reference Documentation

**ALWAYS read `docs/CSS-CONVERTER-PR-REFERENCE.md` before making changes!**

This file contains complete implementation details from the official PR.

### PR Information

- **PR #32856**: https://github.com/elementor/elementor/pull/32856
- **Branch**: `hein/convert-css-to-widgets`
- **Repository**: `elementor/elementor`

### Local PR Code Reference

The PR code is available locally at:
`/Users/janvanvlastuin1981/Local Sites/elementor/app/public/wp-content/plugins/elementor-css/modules/css-converter/`

Key directories:
- `services/atomic-widgets/` - HTML to widget conversion classes
- `convertors/css-properties/` - CSS property mappers
- `convertors/atomic-properties/` - Atomic property type mappers

### Fetching Code from PR (Remote)

```bash
# Fetch a specific mapper file from the PR branch
curl -s "https://raw.githubusercontent.com/elementor/elementor/hein/convert-css-to-widgets/modules/css-converter/convertors/css-properties/properties/FILENAME.php"

# Example: fetch background-property-mapper.php
curl -s "https://raw.githubusercontent.com/elementor/elementor/hein/convert-css-to-widgets/modules/css-converter/convertors/css-properties/properties/background-property-mapper.php"

# List all PR files (page 2 has the css-converter module)
curl -s "https://api.github.com/repos/elementor/elementor/pulls/32856/files?per_page=100&page=2" | jq -r '.[].filename'
```

### Key Implementation Rules

1. **One "local" style per widget** - Merge props into existing style, don't create duplicates
2. **Radial gradients MUST include `positions`** - Always add this field
3. **Use Elementor PropType classes** - Never create raw `$$type` arrays manually
4. **Dimensions use logical properties** - `top`→`block-start`, `right`→`inline-end`, etc.

---

## Code Style

### Self-Documenting Code Over Comments

Avoid inline code comments. Instead, use descriptive method names that explain what the code does.

**Bad:**
```php
private function is_valid_color_format( string $value ): bool {
    // CSS variables are not supported.
    if ( $this->is_css_variable( $value ) ) {
        return false;
    }

    // Hex colors (#fff or #ffffff).
    if ( str_starts_with( $value, '#' ) && ( strlen( $value ) === 4 || strlen( $value ) === 7 ) ) {
        return ctype_xdigit( substr( $value, 1 ) );
    }

    // RGB/RGBA and HSL/HSLA functions.
    if ( str_starts_with( $value, 'rgb' ) || str_starts_with( $value, 'hsl' ) ) {
        return true;
    }

    // Named colors (red, blue, etc.).
    return $this->is_simple_color_name( $value );
}
```

**Good:**
```php
private function is_valid_color_format( string $value ): bool {
    if ( $this->is_unsupported_css_variable( $value ) ) {
        return false;
    }

    return $this->is_hex_color( $value )
        || $this->is_rgb_or_hsl_function( $value )
        || $this->is_named_color( $value );
}

private function is_unsupported_css_variable( string $value ): bool {
    return str_starts_with( $value, 'var(' );
}

private function is_hex_color( string $value ): bool {
    $is_hex_format = str_starts_with( $value, '#' )
        && ( strlen( $value ) === 4 || strlen( $value ) === 7 );

    return $is_hex_format && ctype_xdigit( substr( $value, 1 ) );
}

private function is_rgb_or_hsl_function( string $value ): bool {
    return str_starts_with( $value, 'rgb' ) || str_starts_with( $value, 'hsl' );
}

private function is_named_color( string $value ): bool {
    return preg_match( '/^[a-zA-Z0-9-]+$/', $value ) === 1;
}
```

### When Comments Are Acceptable

- PHPDoc blocks for public methods (required for WordPress standards)
- TODO/FIXME markers for temporary code
- Warnings about non-obvious behavior (e.g., security implications)
- File headers required by WordPress plugin standards

### Naming Conventions

- Method names should be verbs describing action: `convert_`, `is_`, `has_`, `get_`, `set_`
- Boolean methods start with `is_` or `has_`
- Private helper methods should be specific: `is_hex_color()` not `check_hex()`
- Prefer longer descriptive names over short ambiguous ones
