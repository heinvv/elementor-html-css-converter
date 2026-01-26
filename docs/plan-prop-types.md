# Plan: Add All Atomic Prop Types

## Detailed Comparison: Color Converter

### CSS-Converter (Original)

```php
class Color_Property_Mapper extends Color_Atomic_Property_Mapper_Base {
    private const SUPPORTED_PROPERTIES = ['color'];

    protected function generate_atomic_prop_type( string $property, string $color_value ): ?array {
        return $this->create_color_prop_type( $color_value );
    }
}

// Base class method:
protected function create_color_prop_type( string $color_value ): array {
    return Css_Variable_Aware_Color_Prop_Type::make()->generate( $color_value );
}

// Validation in base:
private function is_valid_color_format( string $value ): bool {
    if ( str_starts_with( $value, 'var(' ) ) {
        return true;  // CSS variables SUPPORTED
    }
    // ... hex, rgb, hsl, named colors
}

// Named colors: hardcoded array of ~27 colors
private function get_named_colors(): array {
    return ['transparent', 'inherit', 'initial', 'unset', 'black', 'white', ...];
}
```

### Our Plugin (Refined)

```php
class Color_Converter extends Property_Converter_Base {
    private const SUPPORTED_PROPERTIES = ['color'];
    private const PATTERN_NAMED_COLOR = '/^[a-zA-Z0-9-]+$/';
    private const TRANSPARENT_RGBA = 'rgba(0,0,0,0)';

    public function convert( string $property, $value ): ?array {
        // ... validation
        return Color_Prop_Type::generate( $normalized );
    }
}

// CSS variables explicitly rejected (for now):
private function is_css_variable( string $value ): bool {
    return str_starts_with( $value, 'var(' );
}

private function is_supported_color_format( string $value ): bool {
    if ( $this->is_css_variable( $value ) ) {
        return false;  // CSS variables NOT supported yet
    }
    // ...
}

// Named colors: regex pattern (accepts ANY valid CSS color name)
private function is_named_color( string $value ): bool {
    return preg_match( self::PATTERN_NAMED_COLOR, $value ) === 1;
}
```

### Key Differences

| Aspect | CSS-Converter | Our Plugin |
|--------|---------------|------------|
| **Prop Type** | `Css_Variable_Aware_Color_Prop_Type` | `Color_Prop_Type` (direct) |
| **CSS Variables** | Supported | Explicitly rejected |
| **Named Colors** | Hardcoded array (~27 colors) | Regex pattern (any alphanumeric) |
| **Transparent** | Handled in `parse_color_value()` | Constant `TRANSPARENT_RGBA` |
| **Method Naming** | `map_to_v4_atomic()` | `convert()` |
| **Base Class** | `Color_Atomic_Property_Mapper_Base` | `Property_Converter_Base` |
| **Inheritance** | 3 levels deep | 2 levels deep |

### Our Improvements

1. **Simpler inheritance** - 2 levels vs 3 levels
2. **Named color validation** - Regex accepts all valid CSS color names, not just 27 hardcoded
3. **Explicit constants** - `TRANSPARENT_RGBA`, `PATTERN_NAMED_COLOR`
4. **Self-documenting methods** - `is_empty_or_none()`, `is_transparent()`, `is_hex_color()`
5. **Direct prop type usage** - Uses Elementor's `Color_Prop_Type` directly

### CSS Variables (Future Work)

Our plugin explicitly rejects CSS variables for now. This is intentional - CSS variable support requires:
1. `Css_Variable_Aware_Color_Prop_Type` wrapper
2. Variable resolution logic
3. Fallback value handling

This is planned for Phase 3.

---

## Critical Analysis: Our Implementation vs CSS-Converter

### Our Current Approach (Color Converter)

```
Color_Converter
├── extends Property_Converter_Base
├── SUPPORTED_PROPERTIES = ['color']
├── convert(property, value) → Color_Prop_Type::generate(value)
└── validation/normalization methods
```

**Characteristics:**
- Single class per property
- Direct call to Elementor's prop type
- Simple validation logic
- No registry/factory pattern
- No CSS variable support
- No shorthand expansion

### CSS-Converter Approach

```
Class_Property_Mapper_Factory
└── Class_Property_Mapper_Registry
    └── Property Mappers (30+)
        ├── Color_Property_Mapper extends Color_Atomic_Property_Mapper_Base
        ├── Font_Size_Property_Mapper extends Atomic_Property_Mapper_Base
        ├── Margin_Property_Mapper extends Atomic_Property_Mapper_Base
        └── ...

Atomic_Property_Mapper_Base
├── create_atomic_size_value()
├── create_atomic_dimensions_value()
├── create_atomic_color_value()
├── create_atomic_string_value()
├── parse_size_value() → Size_Value_Parser
└── parse_shorthand_to_logical_properties()

Color_Atomic_Property_Mapper_Base extends Atomic_Property_Mapper_Base
├── map_to_v4_atomic()
├── parse_color_value()
├── create_color_prop_type() → Css_Variable_Aware_Color_Prop_Type
└── is_valid_color_format()
```

**Characteristics:**
- Registry pattern for mapper lookup
- Factory pattern for creation
- Base classes per value type (color, size, dimensions)
- Helper methods for creating atomic values
- CSS Variable support built-in
- Size value parser for unit handling
- Shorthand property expansion

---

## Key Differences

| Aspect | Our Code | CSS-Converter |
|--------|----------|---------------|
| Architecture | Single converter class | Registry + Factory + Base classes |
| Lookup | Loop through converters | Registry hash lookup |
| Value types | One base class | Specialized bases (Color, Size, Dimensions) |
| CSS Variables | Not supported | Built-in via `Css_Variable_Aware_Color_Prop_Type` |
| Size parsing | Not implemented | `Size_Value_Parser` with unit handling |
| Shorthand expansion | Not implemented | `CSS_Shorthand_Expander` |
| Dimensions | Not implemented | Logical properties (block-start, inline-end) |

---

## All Property Mappers in CSS-Converter

### Color-Based Properties
| Mapper | CSS Properties |
|--------|----------------|
| `Color_Property_Mapper` | color |
| `Background_Color_Property_Mapper` | background-color |
| `Border_Color_Property_Mapper` | border-color, border-*-color |

### Size-Based Properties
| Mapper | CSS Properties |
|--------|----------------|
| `Font_Size_Property_Mapper` | font-size |
| `Width_Property_Mapper` | width, min-width, max-width |
| `Height_Property_Mapper` | height, min-height, max-height |
| `Letter_Spacing_Property_Mapper` | letter-spacing |
| `Word_Spacing_Property_Mapper` | word-spacing |
| `Line_Height_Property_Mapper` | line-height |
| `Opacity_Property_Mapper` | opacity |

### Dimensions-Based Properties (Shorthand → Logical)
| Mapper | CSS Properties |
|--------|----------------|
| `Margin_Property_Mapper` | margin, margin-top/right/bottom/left |
| `Atomic_Padding_Property_Mapper` | padding, padding-top/right/bottom/left |
| `Border_Radius_Property_Mapper` | border-radius, border-*-radius |
| `Border_Width_Property_Mapper` | border-width, border-*-width |

### String-Based Properties (Enum values)
| Mapper | CSS Properties |
|--------|----------------|
| `Display_Property_Mapper` | display |
| `Position_Property_Mapper` | position |
| `Flex_Direction_Property_Mapper` | flex-direction |
| `Text_Align_Property_Mapper` | text-align |
| `Font_Weight_Property_Mapper` | font-weight |
| `Text_Transform_Property_Mapper` | text-transform |
| `Font_Style_Property_Mapper` | font-style |
| `Text_Decoration_Property_Mapper` | text-decoration |
| `Border_Style_Property_Mapper` | border-style |

### Complex Properties
| Mapper | CSS Properties |
|--------|----------------|
| `Box_Shadow_Property_Mapper` | box-shadow |
| `Text_Shadow_Property_Mapper` | text-shadow |
| `Background_Property_Mapper` | background (image, gradient) |
| `Border_Property_Mapper` | border (shorthand) |
| `Transform_Property_Mapper` | transform |
| `Flex_Properties_Mapper` | flex, flex-grow, flex-shrink, flex-basis, gap, justify-content, align-items |
| `Positioning_Property_Mapper` | top, right, bottom, left, inset-* |

---

## Recommended Architecture for Our Plugin

### Phase 1: Refactor Base Architecture

1. **Create Registry Pattern**
   ```
   Converter_Registry (update existing)
   ├── register(property, mapper)
   ├── resolve(property) → mapper
   └── get_supported_properties()
   ```

2. **Create Specialized Base Classes**
   ```
   Property_Mapper_Base (abstract)
   ├── supports(property)
   ├── map_to_atomic(property, value)
   └── get_supported_properties()

   Color_Property_Mapper_Base extends Property_Mapper_Base
   ├── parse_color_value(value)
   └── create_color_atomic(value)

   Size_Property_Mapper_Base extends Property_Mapper_Base
   ├── parse_size_value(value) → {size, unit}
   └── create_size_atomic(size, unit)

   String_Property_Mapper_Base extends Property_Mapper_Base
   ├── get_allowed_values()
   └── create_string_atomic(value)

   Dimensions_Property_Mapper_Base extends Property_Mapper_Base
   ├── parse_shorthand(value) → {block-start, inline-end, ...}
   └── create_dimensions_atomic(dimensions)
   ```

3. **Create Size Value Parser**
   ```
   Size_Value_Parser
   ├── parse(value) → {size: number, unit: string}
   ├── is_valid_unit(unit)
   └── SUPPORTED_UNITS = [px, em, rem, %, vw, vh, ...]
   ```

### Phase 2: Implement Property Mappers (Priority Order)

**High Priority (Most Common)**
1. `Font_Size_Mapper` - size-based
2. `Background_Color_Mapper` - color-based
3. `Padding_Mapper` - dimensions-based
4. `Margin_Mapper` - dimensions-based
5. `Width_Mapper` - size-based
6. `Height_Mapper` - size-based

**Medium Priority (Layout)**
7. `Display_Mapper` - string enum
8. `Position_Mapper` - string enum
9. `Flex_Direction_Mapper` - string enum
10. `Justify_Content_Mapper` - string enum
11. `Align_Items_Mapper` - string enum
12. `Gap_Mapper` - size-based

**Medium Priority (Typography)**
13. `Font_Weight_Mapper` - string enum
14. `Text_Align_Mapper` - string enum
15. `Line_Height_Mapper` - size-based
16. `Letter_Spacing_Mapper` - size-based
17. `Text_Transform_Mapper` - string enum
18. `Font_Style_Mapper` - string enum
19. `Text_Decoration_Mapper` - string

**Lower Priority (Borders)**
20. `Border_Width_Mapper` - dimensions-based
21. `Border_Color_Mapper` - color-based
22. `Border_Style_Mapper` - string enum
23. `Border_Radius_Mapper` - dimensions-based

**Lower Priority (Effects)**
24. `Opacity_Mapper` - size-based (percentage)
25. `Box_Shadow_Mapper` - complex
26. `Transform_Mapper` - complex

### Phase 3: Add CSS Variable Support

1. Create `Css_Variable_Aware_Prop_Type` wrapper
2. Detect `var(--...)` syntax
3. Pass through to atomic format with variable reference

### Phase 4: Add Shorthand Expansion

1. Create `Shorthand_Expander` service
2. Expand `margin: 10px 20px` → individual properties
3. Expand `padding: 10px` → all directions
4. Expand `border: 1px solid red` → width, style, color

---

## Implementation Guidelines

### Follow Our Refined Approach (From Color_Converter)

All new converters MUST follow the same patterns established in `Color_Converter`:

```php
class Example_Converter extends Property_Converter_Base {
    // 1. Constants for configuration
    private const SUPPORTED_PROPERTIES = ['property-name'];
    private const PATTERN_VALIDATION = '/^...$/';
    private const DEFAULT_VALUE = '...';

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
    private function normalize_value( string $value ): ?string { ... }
    private function is_specific_format( string $value ): bool { ... }
}
```

### Design Principles

1. **Simple inheritance** - Max 2 levels (Converter → Base)
2. **Self-documenting methods** - Names describe what they check (`is_hex_color`, `is_empty_or_none`)
3. **Explicit constants** - No magic strings/numbers in code
4. **Early returns** - Fail fast with null for invalid input
5. **Single responsibility** - Each private method does one thing
6. **Direct prop type usage** - Use Elementor's prop types directly
7. **No CSS variables yet** - Explicitly reject, planned for Phase 3

### Each Converter Must:

1. **Extend `Property_Converter_Base`** (not deeper hierarchies)
2. **Define `SUPPORTED_PROPERTIES`** as private constant
3. **Use Elementor's prop types** via `PropType::generate()`
4. **Return null** for unsupported values (goes to custom CSS)
5. **Be stateless** - no instance state between conversions
6. **Have descriptive method names** - no `process()` or `handle()`

### Elementor Prop Types to Use:

| Value Type | Elementor Prop Type |
|------------|---------------------|
| Color | `Color_Prop_Type::generate($color)` |
| Size | `Size_Prop_Type::generate(['size' => $n, 'unit' => $u])` |
| String | `String_Prop_Type::generate($value)` |
| Number | `Number_Prop_Type::generate($value)` |
| Dimensions | `Dimensions_Prop_Type::generate($dimensions)` |
| Border Radius | `Border_Radius_Prop_Type::generate($corners)` |
| Border Width | `Border_Width_Prop_Type::generate($widths)` |
| Box Shadow | `Box_Shadow_Prop_Type::generate($shadow)` |

---

## File Structure

```
includes/
├── converters/
│   ├── base/
│   │   ├── class-property-mapper-base.php
│   │   ├── class-color-property-mapper-base.php
│   │   ├── class-size-property-mapper-base.php
│   │   ├── class-string-property-mapper-base.php
│   │   └── class-dimensions-property-mapper-base.php
│   ├── properties/
│   │   ├── class-color-mapper.php
│   │   ├── class-background-color-mapper.php
│   │   ├── class-font-size-mapper.php
│   │   ├── class-padding-mapper.php
│   │   ├── class-margin-mapper.php
│   │   ├── class-width-mapper.php
│   │   ├── class-display-mapper.php
│   │   └── ... (30+ files)
│   └── parsers/
│       ├── class-size-value-parser.php
│       └── class-shorthand-expander.php
├── class-converter-registry.php (update)
└── class-css-converter.php (update)
```

---

## Testing Strategy

Each mapper needs unit tests for:
1. Valid value conversion
2. Invalid value handling (returns null)
3. Edge cases (empty, whitespace, special chars)
4. All supported properties

Example test cases for `Font_Size_Mapper`:
- `16px` → `{$$type: 'size', value: {size: 16, unit: 'px'}}`
- `1.5em` → `{$$type: 'size', value: {size: 1.5, unit: 'em'}}`
- `100%` → `{$$type: 'size', value: {size: 100, unit: '%'}}`
- `invalid` → `null`
- `var(--font-size)` → CSS variable format (Phase 3)
