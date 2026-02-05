# Advanced Breakpoint Support

## Overview

Advanced breakpoint features beyond basic max-width media queries, including min-width conversion strategies, complex media queries, and edge cases.

## Min-Width Media Queries

### Challenge

Elementor breakpoints primarily use `max-width`:
- `mobile`: max-width 767px
- `tablet`: max-width 1024px
- `laptop`: max-width 1366px
- Only `widescreen`: min-width 2400px

CSS often uses `min-width` for mobile-first approaches:
```css
@media (min-width: 768px) {
  .container { display: flex; }
}
```

### Conversion Strategy: Min-Width to Max-Width

Convert `min-width` queries to `max-width` by using the next breakpoint down.

**Logic**:
- `@media (min-width: 768px)` means "768px and above"
- Equivalent to "not below 768px" = "above 767px"
- Map to next Elementor breakpoint that covers this range
- Use desktop for large min-width values

**Mapping rules**:

1. **Min-width matches Elementor breakpoint + 1px**:
   - `min-width: 768px` → `max-width: 767px` (mobile)
   - `min-width: 1025px` → `max-width: 1024px` (tablet)
   - `min-width: 1201px` → `max-width: 1200px` (tablet_extra)

2. **Min-width between breakpoints**:
   - `min-width: 900px` → find next Elementor breakpoint above 900px
   - 900px is between mobile (767px) and tablet (1024px)
   - Apply to tablet breakpoint (1024px max-width)

3. **Min-width above all breakpoints**:
   - `min-width: 1400px` → desktop (no media query)
   - `min-width: 2500px` → widescreen (if enabled)

4. **Min-width below mobile**:
   - `min-width: 500px` → mobile breakpoint
   - All mobile styles apply

**Implementation**:

```php
public function convert_min_width_to_max_width(int $min_width): ?string
{
    $breakpoints = $this->get_breakpoints_config();
    
    // Sort breakpoints by value (ascending)
    $sorted = $this->sort_breakpoints_by_value($breakpoints, 'asc');
    
    // Find first breakpoint where min_width <= breakpoint_value + 1
    foreach ($sorted as $name => $config) {
        if ($config['direction'] === 'max' && $min_width <= $config['value'] + 1) {
            return $name;
        }
    }
    
    // If min_width is very large, use desktop
    if ($min_width > 1400) {
        return 'desktop';
    }
    
    // Fallback to mobile
    return 'mobile';
}
```

**Edge cases**:
- `min-width: 0px` or `min-width: 1px` → desktop (applies everywhere)
- `min-width: 2400px` → widescreen (if enabled)
- Very large values → desktop

### Alternative: Invert Logic

Store min-width styles as desktop base styles, then override at smaller breakpoints.

**Example**:
```css
/* Original */
@media (min-width: 768px) {
  .container { display: flex; }
}
```

**Converted to**:
- Desktop: `display: flex` (base)
- Mobile: `display: block` (override, if needed)

**Limitation**: Requires knowing what the "default" should be for smaller screens. Not always possible.

### Recommended Approach

**Primary strategy**: Convert min-width to max-width using next breakpoint mapping.

**Fallback**: If conversion is unclear, store as desktop styles with a warning.

## Complex Media Queries

### Multiple Conditions

**Example**:
```css
@media screen and (min-width: 768px) and (max-width: 1024px) {
  .container { ... }
}
```

**Strategy**:
- Parse both conditions
- If both `min-width` and `max-width`:
  - Use `max-width` value (more specific)
  - Verify `min-width` doesn't conflict
- If conflict (min > max), skip or use desktop

**Implementation**:
```php
public function parse_complex_media_query(string $query): ?array
{
    // Extract min-width and max-width
    preg_match('/min-width:\s*(\d+)px/', $query, $min_match);
    preg_match('/max-width:\s*(\d+)px/', $query, $max_match);
    
    $min_width = $min_match[1] ?? null;
    $max_width = $max_match[1] ?? null;
    
    if ($min_width && $max_width) {
        // Range query: use max-width (more specific)
        if ($min_width <= $max_width) {
            return ['breakpoint' => $this->match_max_width($max_width), 'type' => 'range'];
        }
        // Invalid range
        return null;
    }
    
    if ($max_width) {
        return ['breakpoint' => $this->match_max_width($max_width), 'type' => 'max'];
    }
    
    if ($min_width) {
        return ['breakpoint' => $this->convert_min_width($min_width), 'type' => 'min'];
    }
    
    return null;
}
```

### Orientation Queries

**Example**:
```css
@media (max-width: 768px) and (orientation: portrait) {
  .container { ... }
}
```

**Strategy**:
- Parse `orientation` but ignore it
- Use `max-width` value for breakpoint matching
- Note: Elementor doesn't distinguish orientation, so treat as regular breakpoint

### Screen Type

**Example**:
```css
@media screen and (max-width: 768px) { ... }
@media print and (max-width: 768px) { ... }
```

**Strategy**:
- Parse `screen`/`print` but only process `screen`
- Skip `print` media queries (not relevant for Elementor)
- Default to `screen` if not specified

## Breakpoint Range Queries

### Between Two Breakpoints

**Example**:
```css
@media (min-width: 768px) and (max-width: 1024px) {
  .container { ... }
}
```

**Strategy**:
- This targets tablet range specifically
- Map to `tablet` breakpoint
- More precise than single condition

**Implementation**:
```php
public function match_range_query(int $min_width, int $max_width): ?string
{
    // Find breakpoint that best fits the range
    $breakpoints = $this->get_breakpoints_config();
    
    foreach ($breakpoints as $name => $config) {
        if ($config['direction'] !== 'max') {
            continue;
        }
        
        $bp_value = $config['value'];
        
        // Check if range fits within breakpoint
        // Range should be mostly within breakpoint's range
        if ($min_width >= ($bp_value - 100) && $max_width <= ($bp_value + 100)) {
            return $name;
        }
    }
    
    return null;
}
```

## Custom Breakpoint Values

### Non-Standard Widths

**Example**:
```css
@media (max-width: 900px) { ... }
@media (max-width: 1500px) { ... }
```

**Strategy**:
- Find closest Elementor breakpoint
- Use tolerance threshold (e.g., ±100px)
- If too far from any breakpoint, skip or use desktop

**Tolerance logic**:
```php
private const BREAKPOINT_TOLERANCE = 100; // pixels

public function match_with_tolerance(int $width, string $direction): ?string
{
    $breakpoints = $this->get_breakpoints_config();
    $closest = null;
    $min_diff = PHP_INT_MAX;
    
    foreach ($breakpoints as $name => $config) {
        if ($config['direction'] !== $direction || !$config['is_enabled']) {
            continue;
        }
        
        $diff = abs($width - $config['value']);
        
        if ($diff < $min_diff && $diff <= self::BREAKPOINT_TOLERANCE) {
            $min_diff = $diff;
            $closest = $name;
        }
    }
    
    return $closest;
}
```

## Nested Media Queries

### CSS Nesting

**Example**:
```css
@media (max-width: 1024px) {
  .container {
    display: flex;
  }
  
  @media (max-width: 768px) {
    .container {
      flex-direction: column;
    }
  }
}
```

**Strategy**:
- Flatten nested queries
- Inner query overrides outer for same selector
- Process from outermost to innermost
- Final styles = outermost + innermost overrides

**Implementation**:
```php
public function flatten_nested_media_queries(string $css): array
{
    $result = [];
    $stack = [];
    
    // Parse nested structure
    // Outer: max-width 1024px
    // Inner: max-width 768px
    
    // Result: 
    // - tablet: display: flex
    // - mobile: display: flex, flex-direction: column
}
```

**Limitation**: Complex nesting may not be fully supported. Consider warning for deeply nested queries.

## Viewport Units in Media Queries

### Relative Units

**Example**:
```css
@media (max-width: 50vw) { ... }
@media (min-width: 20em) { ... }
```

**Strategy**:
- Convert relative units to pixels if possible
- Use viewport width assumptions (e.g., 1920px desktop)
- If conversion unclear, skip or use desktop

**Conversion**:
```php
public function convert_relative_to_pixels(string $value, string $unit): ?int
{
    // Assumptions for conversion
    $viewport_width = 1920; // Desktop assumption
    $base_font_size = 16; // px
    
    switch ($unit) {
        case 'vw':
            return (int)($value * $viewport_width / 100);
        case 'em':
        case 'rem':
            return (int)($value * $base_font_size);
        case 'px':
            return (int)$value;
        default:
            return null;
    }
}
```

**Note**: This is approximate. May not match actual viewport.

## Media Query Precedence

### Multiple Queries for Same Selector

**Example**:
```css
.container { color: blue; }
@media (max-width: 1024px) {
  .container { color: red; }
}
@media (max-width: 768px) {
  .container { color: green; }
}
```

**Strategy**:
- Desktop: `color: blue`
- Tablet: `color: red` (overrides desktop)
- Mobile: `color: green` (overrides tablet)

**Implementation**:
- Process media queries in order (top to bottom)
- Later queries override earlier ones for same breakpoint
- Desktop styles are base, responsive override

## Performance Considerations

### Large CSS Files

**Optimization**:
- Parse media queries once, cache results
- Only process enabled breakpoints
- Skip disabled breakpoints early

**Caching**:
```php
private $parsed_cache = [];

public function parse_with_cache(string $css): array
{
    $cache_key = md5($css);
    
    if (isset($this->parsed_cache[$cache_key])) {
        return $this->parsed_cache[$cache_key];
    }
    
    $result = $this->parse_media_queries($css);
    $this->parsed_cache[$cache_key] = $result;
    
    return $result;
}
```

## Error Handling

### Invalid Media Queries

**Cases**:
- Malformed syntax
- Unsupported features
- Conflicting conditions

**Strategy**:
- Log warnings for unsupported queries
- Skip invalid queries (don't break conversion)
- Continue processing valid queries
- Return warnings in conversion result

**Implementation**:
```php
public function parse_with_errors(string $css): array
{
    $result = ['queries' => [], 'warnings' => []];
    
    try {
        $queries = $this->parse_media_queries($css);
        $result['queries'] = $queries;
    } catch (Exception $e) {
        $result['warnings'][] = 'Failed to parse media queries: ' . $e->getMessage();
    }
    
    return $result;
}
```

## Future Enhancements

### Potential Features

1. **Custom breakpoint mapping**:
   - Allow user-defined CSS → Elementor breakpoint mappings
   - Config file for custom rules

2. **Breakpoint aliases**:
   - Support common breakpoint names (sm, md, lg)
   - Map to Elementor breakpoints

3. **Media query validation**:
   - Warn about unsupported features
   - Suggest alternatives

4. **Breakpoint preview**:
   - Show which Elementor breakpoint a CSS query maps to
   - Help users understand conversion

## Testing Advanced Cases

### Test Cases

1. **Min-width conversion**:
   - `min-width: 768px` → mobile
   - `min-width: 1025px` → tablet
   - `min-width: 2000px` → desktop

2. **Complex queries**:
   - Range queries
   - Multiple conditions
   - Orientation queries

3. **Edge cases**:
   - Invalid syntax
   - Unsupported units
   - Nested queries

4. **Performance**:
   - Large CSS files
   - Many media queries
   - Caching behavior

## Implementation Priority

1. **High**: Min-width to max-width conversion
2. **Medium**: Complex queries (range, multiple conditions)
3. **Low**: Nested queries, viewport units
4. **Future**: Custom mappings, validation

## Notes

- All features work within converter plugin only
- No changes to Elementor core required
- Use existing Elementor breakpoint API
- Maintain backward compatibility
