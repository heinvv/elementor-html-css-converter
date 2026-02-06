# Support Max-Width Media Queries

## Overview

Add support for CSS `@media (max-width: Xpx)` queries in the HTML/CSS converter. Map CSS breakpoint values to Elementor's breakpoint system and create responsive style variants.

## Elementor Breakpoint System

Elementor uses a breakpoint system accessible via `Plugin::$instance->breakpoints->get_breakpoints_config()`. Most breakpoints use `max-width`:

- `mobile`: max-width 767px (always enabled)
- `mobile_extra`: max-width 880px
- `tablet`: max-width 1024px (always enabled)
- `tablet_extra`: max-width 1200px
- `laptop`: max-width 1366px
- `desktop`: base styles (no media query)
- `widescreen`: min-width 2400px (only min-width breakpoint)

Breakpoint config structure:
```php
[
  'mobile' => [
    'value' => 767,
    'direction' => 'max',
    'is_enabled' => true
  ],
  // ...
]
```

## Current State

### Limitations

1. **Class Extractor** (`class-class-extractor.php` line 50):
   - Strips all `@media` queries: `preg_replace('/@media[^{]+\{([^{}]*\{[^}]*\})*[^}]*\}/s', '', $css)`
   - Only extracts desktop styles

2. **ID Style Extractor** (`class-id-style-extractor.php`):
   - Only parses simple `#id { ... }` selectors
   - No media query parsing
   - Returns flat structure: `['id' => ['property' => 'value']]`

3. **Style Integration**:
   - `Widget_Styles_Integrator` creates single desktop variant
   - `Style_Definition_Builder` hardcodes `'breakpoint' => 'desktop'`

### Atomic Widgets Support

Elementor atomic widgets fully support responsive styles:
- Variants can have `meta.breakpoint` set to any Elementor breakpoint name
- `Styles_Renderer` groups variants by breakpoint and wraps with media queries
- Multiple variants per style definition are supported

## Implementation Plan

### Phase 1: Media Query Parser

**New class**: `includes/converters/css/class-media-query-parser.php`

Parse `@media` blocks from CSS and extract breakpoint information.

**Key methods**:
```php
public function parse_media_queries(string $css): array
// Returns: [
//   ['breakpoint' => 'tablet', 'css' => '#id { color: red; }'],
//   ['breakpoint' => 'mobile', 'css' => '#id { font-size: 14px; }']
// ]

public function extract_desktop_css(string $css): string
// Returns CSS without @media blocks
```

**Parsing logic**:
- Use regex to match `@media (max-width: Xpx) { ... }`
- Extract width value and direction
- Handle nested braces correctly
- Support simple queries: `@media (max-width: 768px)`
- Support with parentheses: `@media screen and (max-width: 768px)`

**Regex pattern**:
```php
/@media\s+(?:[^{]*?\()?\s*(?:max-width|min-width)\s*:\s*(\d+)px\s*\)?\s*\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/s
```

### Phase 2: Breakpoint Matcher

**New class**: `includes/converters/css/class-breakpoint-matcher.php`

Map CSS media query widths to Elementor breakpoint names.

**Key methods**:
```php
public function match_css_to_elementor_breakpoint(int $width, string $direction = 'max'): ?string
// Returns: 'tablet', 'mobile', etc. or null if no match

public function get_breakpoints_config(): array
// Wrapper for Plugin::$instance->breakpoints->get_breakpoints_config()
```

**Matching strategy**:
1. Query Elementor breakpoint config via `Plugin::$instance->breakpoints->get_breakpoints_config()`
2. Filter to enabled breakpoints with matching direction (`max` for max-width)
3. Find closest match by absolute difference
4. Fallback to `null` (will use desktop) if no match found

**Example matching**:
- CSS `max-width: 768px` → closest to `mobile: 767px` → returns `'mobile'`
- CSS `max-width: 1024px` → exact match `tablet: 1024px` → returns `'tablet'`
- CSS `max-width: 900px` → closest `tablet: 1024px` → returns `'tablet'`
- CSS `max-width: 500px` → closest `mobile: 767px` → returns `'mobile'`

**Tolerance**:
- Prefer exact matches
- If no exact match, use closest enabled breakpoint
- If difference > 200px, consider skipping (too far from any breakpoint)

### Phase 3: ID-Based Styles Enhancement

**Modify**: `includes/converters/html/class-id-style-extractor.php`

**Changes**:
1. Add `parse_id_rules_with_breakpoints()` method
2. Parse media queries before extracting ID rules
3. Extract ID rules per breakpoint
4. Return structure: `['id' => ['desktop' => [...], 'tablet' => [...], 'mobile' => [...]]]`

**New method signature**:
```php
public function parse_id_rules_with_breakpoints(string $css, Breakpoint_Matcher $matcher): array
// Returns: [
//   'container' => [
//     'desktop' => ['display' => 'flex', 'gap' => '20px'],
//     'tablet' => ['display' => 'block'],
//     'mobile' => ['padding' => '10px']
//   ]
// ]
```

**Implementation steps**:
1. Use `Media_Query_Parser` to extract media query blocks
2. Extract desktop CSS (non-media-query rules)
3. For each media query block:
   - Match CSS breakpoint to Elementor breakpoint
   - Extract ID rules from that block's CSS
   - Store under breakpoint key
4. Merge desktop rules with responsive rules

**Update**: `includes/converters/html/class-atomic-data-parser.php`
- Use new `parse_id_rules_with_breakpoints()` method
- Handle multi-breakpoint structure in `extract_element_data()`
- Convert styles per breakpoint to atomic props
- Pass breakpoint info to style integrator

### Phase 4: Class-Based Styles Enhancement

**Modify**: `includes/converters/classes/class-class-extractor.php`

**Changes**:
1. Remove media query stripping (line 50)
2. Add `extract_from_css_with_breakpoints()` method
3. Parse media queries first, then extract classes per breakpoint
4. Return structure: `['class-name' => ['desktop' => [...], 'tablet' => [...]]]`

**New method**:
```php
public function extract_from_css_with_breakpoints(string $css, Breakpoint_Matcher $matcher): array
// Returns: [
//   'btn-primary' => [
//     'desktop' => ['selector' => '.btn-primary', 'properties' => [...]],
//     'tablet' => ['selector' => '.btn-primary', 'properties' => [...]]
//   ]
// ]
```

**Update**: `includes/converters/classes/class-class-conversion-service.php`
- Accept breakpoint-aware class data
- Convert classes per breakpoint
- Create multiple variants per global class

**Update**: `includes/converters/classes/class-class-registration-service.php`
- Register global classes with multiple variants
- Each variant has appropriate `meta.breakpoint`

### Phase 5: Style Integration Updates

**Modify**: `includes/converters/html/class-widget-styles-integrator.php`

**Update `create_styles_structure()`**:
- Accept breakpoint-aware atomic props: `['desktop' => [...], 'tablet' => [...]]`
- Create multiple variants (one per breakpoint)
- Ensure desktop variant exists (base styles)

**New signature**:
```php
private function create_styles_structure(string $class_id, array $breakpoint_props): array
// $breakpoint_props: ['desktop' => [...], 'tablet' => [...], 'mobile' => [...]]
```

**Variant creation**:
- Always create desktop variant first (base styles)
- Add responsive variants for other breakpoints
- Use `Style_Definition_Builder::create_variant()` with breakpoint parameter

**Modify**: `includes/converters/css/class-style-definition-builder.php`

**Add method**:
```php
public function build_with_breakpoints(array $breakpoint_props, string $widget_id): array
// Creates style definition with multiple variants
// $breakpoint_props: ['desktop' => [...], 'tablet' => [...], 'mobile' => [...]]
```

**Implementation**:
- Generate single style ID
- Create variant for each breakpoint
- Desktop variant always included (even if empty)
- Responsive variants only included if they have properties

### Phase 6: HTML Converter Integration

**Modify**: `includes/converters/html/class-html-converter.php`

**Update `convert_html_to_atomic_widgets()`**:
1. Initialize `Breakpoint_Matcher` instance
2. Pass matcher to `Atomic_Data_Parser` and `Class_Extractor`
3. Ensure breakpoint-aware data flows through pipeline

**Changes**:
- Inject `Breakpoint_Matcher` into `Atomic_Data_Parser` constructor
- Pass matcher to `Id_Style_Extractor` methods
- Pass matcher to `Class_Extractor` methods

## File Structure

### New Files

1. `includes/converters/css/class-media-query-parser.php`
   - Parse `@media` blocks from CSS
   - Extract breakpoint conditions

2. `includes/converters/css/class-breakpoint-matcher.php`
   - Match CSS breakpoints to Elementor breakpoints
   - Query Elementor breakpoint config

### Modified Files

1. `includes/converters/html/class-id-style-extractor.php`
   - Add breakpoint-aware parsing

2. `includes/converters/html/class-atomic-data-parser.php`
   - Handle multi-breakpoint ID rules

3. `includes/converters/classes/class-class-extractor.php`
   - Remove media query stripping
   - Add breakpoint-aware extraction

4. `includes/converters/html/class-widget-styles-integrator.php`
   - Support multiple variants per style

5. `includes/converters/css/class-style-definition-builder.php`
   - Add `build_with_breakpoints()` method

6. `includes/converters/html/class-html-converter.php`
   - Integrate breakpoint matching

7. `includes/converters/classes/class-class-conversion-service.php`
   - Handle breakpoint-aware classes

8. `includes/converters/classes/class-class-registration-service.php`
   - Register multi-variant global classes

## Example Conversion

### Input HTML/CSS

```html
<style>
#container {
  display: flex;
  gap: 20px;
}

@media (max-width: 1024px) {
  #container {
    display: block;
  }
}

@media (max-width: 767px) {
  #container {
    padding: 10px;
  }
}
</style>
<div id="container">Content</div>
```

### Output Widget Structure

```json
{
  "widgetType": "e-div",
  "styles": {
    "e-container-abc123": {
      "id": "e-container-abc123",
      "type": "class",
      "label": "local",
      "variants": [
        {
          "meta": {
            "breakpoint": "desktop",
            "state": null
          },
          "props": {
            "display": {"$$type": "string", "value": "flex"},
            "gap": {"$$type": "size", "size": 20, "unit": "px"}
          }
        },
        {
          "meta": {
            "breakpoint": "tablet",
            "state": null
          },
          "props": {
            "display": {"$$type": "string", "value": "block"}
          }
        },
        {
          "meta": {
            "breakpoint": "mobile",
            "state": null
          },
          "props": {
            "padding": {"$$type": "dimensions", "block-start": {"size": 10, "unit": "px"}}
          }
        }
      ]
    }
  }
}
```

## Testing Strategy

### Unit Tests

1. **Media Query Parser**:
   - Simple: `@media (max-width: 768px) { #id { color: red; } }`
   - With screen: `@media screen and (max-width: 768px) { ... }`
   - Multiple media queries
   - Nested braces

2. **Breakpoint Matcher**:
   - Exact matches (768px → mobile: 767px)
   - Closest matches (900px → tablet: 1024px)
   - No match (fallback to desktop)
   - Disabled breakpoints (skip)

3. **ID Style Extractor**:
   - Desktop + responsive styles
   - Multiple breakpoints per ID
   - Merge behavior

4. **Class Extractor**:
   - Classes within media queries
   - Multiple breakpoints per class

### Integration Tests

1. Full HTML conversion with responsive CSS
2. Class import with responsive styles
3. Mixed desktop + responsive styles
4. Edge cases: no desktop styles, only responsive

## Edge Cases

1. **No desktop styles**: Only media query styles
   - Create empty desktop variant

2. **Multiple media queries for same selector**:
   - Merge properties (last wins within same breakpoint)

3. **CSS breakpoint doesn't match Elementor breakpoint**:
   - Use closest match or fallback to desktop

4. **Disabled Elementor breakpoints**:
   - Skip styles for disabled breakpoints

5. **Media query with multiple conditions**:
   - Only parse `max-width` condition, ignore others

6. **Nested media queries**:
   - Flatten to outer breakpoint (don't support nesting)

## Backward Compatibility

- Existing desktop-only conversions continue to work
- New breakpoint-aware methods are additive
- Old methods remain for simple cases
- No breaking changes to API

## Dependencies

- **Elementor Breakpoints API**: `Plugin::$instance->breakpoints->get_breakpoints_config()`
- **No external CSS parser**: Use regex/PHP string parsing (matches current approach)
- **No Elementor code changes**: Work entirely within converter plugin

## Implementation Order

1. Create `Media_Query_Parser` class
2. Create `Breakpoint_Matcher` class
3. Update `Id_Style_Extractor` for breakpoints
4. Update `Atomic_Data_Parser` to handle breakpoints
5. Test ID-based responsive styles
6. Update `Class_Extractor` for breakpoints
7. Update class conversion pipeline
8. Test class-based responsive styles
9. Update style integration
10. Full integration testing
