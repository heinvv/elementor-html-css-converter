# Plan: Port HTML to Widget Converters from css-converter Module

## Overview

Port the HTML to Elementor atomic widget conversion code from the PR at `/elementor-css/modules/css-converter/` to the `elementor-html-css-converter` plugin.

## Files to Port

### From `/elementor-css/modules/css-converter/services/atomic-widgets/`

| Source File | Description |
|-------------|-------------|
| `html-to-atomic-widget-mapper.php` | Maps HTML tags (h1-h6, p, button, img, div, etc.) to atomic widget types |
| `atomic-widget-settings-preparer.php` | Prepares widget settings (title, text, link, src) based on widget type |
| `atomic-widget-json-creator.php` | Creates widget JSON using Widget_Builder and Element_Builder |
| `atomic-data-parser.php` | Parses HTML using DOMDocument, extracts elements recursively |
| `atomic-widgets-orchestrator.php` | Main orchestrator coordinating the conversion pipeline |
| `widget-styles-integrator.php` | Integrates atomic props as styles into widgets |
| `atomic-widget-class-generator.php` | Generates unique class IDs for widgets |

## Supported HTML Elements → Atomic Widgets

| HTML Element | Atomic Widget | Content/Props |
|--------------|---------------|---------------|
| h1-h6 | e-heading | title, tag, level |
| p | e-paragraph | text |
| blockquote | e-paragraph | text |
| button | e-button | text, link |
| a | e-button | text, link.destination, link.isTargetBlank |
| img | e-image | src, alt, width, height |
| div, section, article, header, footer, main, aside, nav | e-flexbox | children |
| span | e-flexbox | children |

## Architecture to Port

```
Atomic_Widgets_Orchestrator (main entry point)
├── Atomic_Data_Parser
│   ├── HTML_To_Atomic_Widget_Mapper
│   ├── Id_Style_Extractor (extracts #id CSS rules only)
│   └── Uses existing Converter_Registry for CSS props
├── Atomic_Widget_JSON_Creator
│   ├── HTML_To_Atomic_Widget_Mapper
│   └── Atomic_Widget_Settings_Preparer
└── Widget_Styles_Integrator
    └── Atomic_Widget_Class_Generator
```

## Proposed Folder Structure (Flat)

```
/includes
├── converters/
│   ├── css/                                    # Move existing CSS converters here
│   │   ├── class-color-converter.php
│   │   └── ... (all existing converters)
│   └── html/                                   # NEW: HTML conversion
│       ├── class-html-to-atomic-widget-mapper.php
│       ├── class-atomic-widget-settings-preparer.php
│       ├── class-atomic-widget-json-creator.php
│       ├── class-atomic-data-parser.php
│       ├── class-widget-styles-integrator.php
│       └── class-atomic-widget-class-generator.php
├── interfaces/
│   └── (existing interfaces)
├── abstracts/
│   └── (existing abstracts)
├── parsers/
│   ├── (existing parsers)
│   └── class-id-style-extractor.php            # NEW: ID-based CSS extraction
├── class-converter-registry.php               # Existing
├── class-css-converter.php                    # Existing
├── class-html-converter.php                   # NEW: Main HTML conversion orchestrator
├── class-rest-api.php                         # Update with new endpoints
└── class-plugin.php                           # Update to initialize HTML conversion
```

## Key Conversion Flow

1. **Input**: HTML string with optional embedded `<style>` tags
   ```html
   <style>#heading { color: red; }</style>
   <div><h1 id="heading">Title</h1><p>Text</p></div>
   ```

2. **Parse HTML** (Atomic_Data_Parser):
   - Load HTML into DOMDocument
   - Extract CSS from `<style>` tags using `Id_Style_Extractor`
   - Parse ID rules (only `#id { ... }` selectors supported)
   - Recursively extract elements with tags, attributes, content, children
   - Map each element to widget type using HTML_To_Atomic_Widget_Mapper
   - Convert ID-based styles to atomic props (inline styles are ignored)

3. **Create Widgets** (Atomic_Widget_JSON_Creator):
   - For each element, prepare settings using Atomic_Widget_Settings_Preparer
   - Use Widget_Builder for content widgets (e-heading, e-paragraph, e-button, e-image)
   - Use Element_Builder for containers (e-flexbox) with recursive children

4. **Integrate Styles** (Widget_Styles_Integrator):
   - Generate class IDs using Atomic_Widget_Class_Generator
   - Create styles structure with variants
   - Add class references to widget settings

5. **Output**: Array of Elementor widget JSON structures

## Integration with Existing Code

### CSS_To_Atomic_Props_Converter Integration

The PR's `CSS_To_Atomic_Props_Converter` uses a different registry pattern. We need to adapt it to use our existing `Converter_Registry`:

```php
// Current plugin pattern
$converter = $this->registry->resolve( $property );
$result = $converter->convert( $property, $value );

// Adapt to match PR's interface
public function convert_css_to_atomic_prop( string $property, $value ): ?array {
    $converter = $this->registry->resolve( $property );
    if ( ! $converter ) {
        return null;
    }
    return $converter->convert( $property, $value );
}
```

### REST API Endpoint

Add new endpoint for HTML conversion:

```php
register_rest_route( 'html-css-converter/v1', '/convert-html', [
    'methods' => 'POST',
    'callback' => [ $this, 'handle_convert_html_request' ],
    'permission_callback' => [ $this, 'check_permissions' ],
    'args' => [
        'html' => [
            'required' => true,
            'type' => 'string',
        ],
        'options' => [
            'required' => false,
            'type' => 'object',
        ],
        'postId' => [
            'required' => false,
            'type' => 'integer',
            'description' => 'Elementor post ID to insert widgets into',
        ],
        'widgetId' => [
            'required' => false,
            'type' => 'string',
            'description' => 'Container widget ID to insert widgets into',
        ],
    ],
]);
```

When `postId` and `widgetId` are provided, the converted widgets are automatically inserted into the specified container within the Elementor document. The response includes:
- `inserted`: true when widgets were inserted
- `widget_ids`: array of new widget IDs
- `post_id`: the post ID
- `edit_url`: URL to edit the post in Elementor

## Implementation Phases

### Phase 1: Restructure Folders
1. Create `/converters/css/` and `/converters/html/` folders
2. Move existing CSS converters to `/converters/css/`
3. Update `ehcc_load_files()` with new paths

### Phase 2: Port HTML Conversion Classes
1. Port `HTML_To_Atomic_Widget_Mapper` → `/converters/html/`
2. Port `Atomic_Widget_Settings_Preparer` → `/converters/html/`
3. Port `Atomic_Widget_JSON_Creator` → `/converters/html/`
4. Port `Atomic_Widget_Class_Generator` → `/converters/html/`
5. Port `Atomic_Data_Parser` → `/converters/html/`
6. Port `Widget_Styles_Integrator` → `/converters/html/`

### Phase 3: Create Main Orchestrator
1. Create `Html_Converter` class at `/includes/class-html-converter.php`
   - Simplified version of `Atomic_Widgets_Orchestrator`
   - Uses existing `Converter_Registry` for CSS props

### Phase 4: Integration
1. Update `Rest_Api` with `/convert-html` endpoint
2. Update `Plugin` class initialization

### Phase 5: ID-Based CSS Styling
1. Create `Id_Style_Extractor` class
2. Update `Atomic_Data_Parser` to use ID styles
3. Update file loader

### Phase 6: Testing
1. Unit tests for each ported class
2. Integration tests for full conversion pipeline
3. Manual testing with various HTML samples

---

## Enhancement: ID-Based CSS Styling

### Design Decision

**ID-only CSS matching** - to avoid specificity complexity:
- Only support `#id { ... }` selectors
- No class selectors, no tag selectors, no inline styles
- No specificity calculation needed (1 ID = 1 element = 1 rule)
- CSS comes embedded in `<style>` tags within the HTML
- Inline `style=""` attributes are ignored

**Future enhancement**: Atomic Global Classes support can be added later.

### Example Input

```html
<style>
#container { display: flex; gap: 20px; }
#heading { color: red; font-size: 24px; }
#link { background-color: blue; padding: 10px; }
</style>
<div id="container">
  <h1 id="heading">Title</h1>
  <a id="link" href="#">Click me</a>
</div>
```

### Expected Output

- `e-flexbox` (id="container") with display:flex, gap:20px atomic props
- `e-heading` (id="heading") with color:red, font-size:24px atomic props
- `e-button` (id="link") with background-color:blue, padding:10px atomic props

### Implementation Approach

Create `Id_Style_Extractor` class in `/includes/parsers/`:

```php
class Id_Style_Extractor {
    /**
     * Extract CSS from <style> tags.
     * @return string Raw CSS content.
     */
    public function extract_style_tags(DOMDocument $dom): string;

    /**
     * Parse CSS and return ID → declarations map.
     * Only processes #id selectors, ignores all others.
     * @return array ['container' => ['display' => 'flex', 'gap' => '20px'], ...]
     */
    public function parse_id_rules(string $css): array;

    /**
     * Get styles for a specific element ID.
     * @return array CSS property-value pairs.
     */
    public function get_styles_for_id(string $id, array $id_rules): array;
}
```

---

## Test Cases

```php
// Simple heading (no styling)
$html = '<h1>Hello World</h1>';
// Expected: e-heading widget with title="Hello World", tag="h1", level=1, NO styles

// Nested structure (no styling)
$html = '<div><h1>Title</h1><p>Content</p></div>';
// Expected: e-flexbox container with 2 children (e-heading, e-paragraph), NO styles

// Button with link (no styling)
$html = '<a href="https://example.com" target="_blank">Click me</a>';
// Expected: e-button with text="Click me", link.destination, link.isTargetBlank=true, NO styles

// Image (no styling)
$html = '<img src="image.jpg" alt="Description" width="200" height="100">';
// Expected: e-image with src, alt, width, height props, NO styles

// Text inside container (text wrapping, no styling)
$html = '<div>Some text</div>';
// Expected: e-flexbox container with 1 child: synthetic e-paragraph with text="Some text"

// ID-based styling
$html = '<style>#title { color: red; }</style><h1 id="title">Hello</h1>';
// Expected: e-heading with color:red atomic prop

// Nested elements with IDs
$html = '<style>
  #box { display: flex; }
  #text { font-size: 16px; }
</style>
<div id="box"><p id="text">Content</p></div>';
// Expected: e-flexbox with display:flex, e-paragraph with font-size:16px

// Inline style ignored (ID only)
$html = '<p style="color: blue;">Text</p>';
// Expected: e-paragraph with NO styles (inline styles not supported)

// Class selector ignored
$html = '<style>.ignored { color: red; }</style><p class="ignored">Text</p>';
// Expected: e-paragraph with NO styles (class selector not supported)

// Element without ID has no styles
$html = '<style>#title { color: red; }</style><h1>No ID here</h1>';
// Expected: e-heading with NO styles (element has no id attribute)
```

## Text Wrapping Behavior

Container elements (div, span, section, article, aside, header, footer, main, nav) with direct text content automatically wrap the text in a synthetic `<p>` element.

**Example transformation:**
```
Input:  <div>Some text</div>
Output: e-flexbox
        └── e-paragraph (synthetic)
            └── text: "Some text"

Input:  <div>Text before<h1>Title</h1></div>
Output: e-flexbox
        ├── e-paragraph (synthetic) - text: "Text before"
        └── e-heading - title: "Title"
```

This is handled by `Html_Parser::wrap_text_content_in_paragraphs()` in the PR.

---

## Verification

1. **Unit tests**: Run `./vendor/bin/phpunit tests/`
2. **Manual API test**:
   ```bash
   curl -X POST "http://localhost/wp-json/html-css-converter/v1/convert-html" \
     -H "Content-Type: application/json" \
     -d '{"html": "<style>#title{color:red}</style><h1 id=\"title\">Test</h1>"}'
   ```
3. **Check widget output matches Elementor's atomic widget schema**
