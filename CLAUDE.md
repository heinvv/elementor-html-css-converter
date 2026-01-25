# Claude Rules for elementor-html-css-converter

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
