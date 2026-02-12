# Fix: Complex Link Conversion

## Problem

The HTML to Elementor converter was struggling with `<a>` tags that contain complex content (nested div, heading, paragraph elements). 

Previously, ALL `<a>` tags were converted to `e-button` widgets, which only works for simple text links. When a link contained complex nested content, the conversion would fail or produce incorrect output with empty buttons.

## Example of Problem Input

```html
<a href="/nl/nieuws/test">
  <div>
    <h2>Article Title</h2>
  </div>
  <p>Article description text here...</p>
</a>
```

This would incorrectly convert to:
```json
{
  "widgetType": "e-button",
  "settings": {
    "text": {"$$type": "html-v2", "value": {"content": null, "children": []}},
    "link": {...}
  }
}
```

## Solution

The fix detects when an `<a>` tag has complex child elements and converts it to an `e-div-block` container with `tag: 'a'` instead of an `e-button`.

### Conversion Logic

1. **Simple text link** → `e-button` widget (existing behavior)
   ```html
   <a href="https://google.com">Click here</a>
   ```

2. **Link with complex content** → `e-div-block` with `tag: 'a'` (new behavior)
   ```html
   <a href="/news/article">
     <div><h2>Title</h2></div>
     <p>Description</p>
   </a>
   ```

### Expected Output

For complex links, the output structure is now:
```json
{
  "elType": "e-div-block",
  "settings": {
    "tag": {"$$type": "string", "value": "a"},
    "link": {
      "$$type": "link",
      "value": {
        "destination": {"$$type": "url", "value": "/news/article"},
        "isTargetBlank": null
      }
    }
  },
  "elements": [
    ...child widgets (div, heading, paragraph)...
  ]
}
```

## Implementation

### Modified Files

#### 1. `class-atomic-data-parser.php`

Added detection logic after extracting children:

```php
$children = $this->extract_children( $element, $svg_map );
$attributes['original_tag'] = $tag_name;

$final_widget_type = $widget_config['type'];
if ( 'a' === $tag_name && ! empty( $children ) ) {
    $final_widget_type = 'e-div-block';
    $attributes['is_link_container'] = true;
}
```

#### 2. `class-atomic-widget-settings-preparer.php`

Added handler for link containers in the `add_content_settings` method:

```php
case 'e-div-block':
    if ( isset( $attributes['is_link_container'] ) && $attributes['is_link_container'] ) {
        $settings['tag'] = $this->create_atomic_prop( 'string', 'a' );
        if ( isset( $attributes['href'] ) ) {
            $settings['link'] = $this->create_link_prop( $attributes['href'], $attributes );
        }
    }
    break;
```

Also updated `filter_attributes` to exclude the internal `is_link_container` flag:

```php
$excluded_attributes = [ 'style', 'class', 'id', 'href', 'src', 'alt', 'original_tag', 'svg_content', 'is_link_container' ];
```

## Testing

### Test Case 1: Complex Link with Nested Content

**Input:**
```html
<a href="/nl/nieuws/test" id="scraped-0">
  <div id="scraped-0-1">
    <h2 id="scraped-0-2">Article Title</h2>
  </div>
  <p id="scraped-0-3">Article description</p>
</a>
```

**Expected Output:**
- Widget type: `e-div-block`
- Has `tag` setting: `{"$$type": "string", "value": "a"}`
- Has `link` setting with correct href
- Contains child elements: div → heading, and paragraph

### Test Case 2: Simple Text Link

**Input:**
```html
<a href="https://google.com">Click here</a>
```

**Expected Output:**
- Widget type: `e-button`
- Has `text` setting with "Click here"
- Has `link` setting with correct href
- No child elements

## Benefits

1. **Correct semantic output**: Complex links are properly represented as containers
2. **Preserves structure**: All nested elements inside the link are maintained
3. **Backward compatible**: Simple text links still convert to buttons as before
4. **Clickable containers**: The entire container acts as a link in the editor and frontend

## Related

- Issue: Converter struggles with `<a>` structures with content
- Pattern follows Elementor's container-with-link approach for wrapping clickable elements
