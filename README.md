# Elementor HTML CSS Converter

Converts HTML with CSS to Elementor atomic widgets.

## Endpoint

```
POST /wp-json/html-css-converter/v1/convert-html
```

## Important: Styling Approach

**Inline styles (`style="..."`) are NOT supported.** Styles must be defined in `<style>` tags using:
- ID selectors: `#element-id { ... }`
- Class selectors: `.class-name { ... }`

## Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `html` | string | required | HTML content with `<style>` tags |
| `import_variables` | boolean | `true` | Extract variables from `:root` in `<style>` tags |
| `import_classes` | boolean | `true` | Create global classes from `.class` selectors |
| `update_mode` | string | `"create_new"` | `"create_new"` or `"update"` for existing variables/classes |
| `postId` | integer | - | Insert widgets into existing post |
| `postTitle` | string | `"Converted HTML"` | Title for auto-created post |
| `postStatus` | string | `"draft"` | Status for auto-created post |

---

## Test Payloads

### Basic: ID Selector with Variables

```json
{
  "html": "<style>\n:root {\n  --primary-color: #ff5733;\n  --spacing-md: 16px;\n}\n#test {\n  color: var(--primary-color);\n  padding: var(--spacing-md);\n}\n</style>\n<div id=\"test\">Test</div>"
}
```

### Basic: Class Selector with Variables

```json
{
  "html": "<style>\n:root {\n  --primary-color: #ffffff;\n  --spacing-md: 16px;\n}\n.card {\n  color: var(--primary-color);\n  padding: var(--spacing-md);\n}\n</style>\n<div class=\"card\">Card content</div>"
}
```

### Multiple Classes on One Element

```json
{
  "html": "<style>\n:root {\n  --brand-blue: #0066cc;\n  --text-lg: 24px;\n}\n.heading {\n  color: var(--brand-blue);\n}\n.large {\n  font-size: var(--text-lg);\n}\n</style>\n<h1 class=\"heading large\">Big Blue Heading</h1>"
}
```

### Nested Elements with Different Classes

```json
{
  "html": "<style>\n:root {\n  --bg-dark: #1a1a1a;\n  --text-light: #f5f5f5;\n  --gap-sm: 8px;\n}\n.container {\n  background-color: var(--bg-dark);\n  padding: var(--gap-sm);\n}\n.content {\n  color: var(--text-light);\n}\n</style>\n<div class=\"container\"><p class=\"content\">Nested text</p></div>"
}
```

### Mixed ID and Class Selectors

```json
{
  "html": "<style>\n:root {\n  --accent: #ff5733;\n  --radius: 8px;\n}\n#wrapper {\n  border-radius: var(--radius);\n}\n.highlight {\n  background-color: var(--accent);\n}\n</style>\n<div id=\"wrapper\" class=\"highlight\">Mixed styles</div>"
}
```

### Variable Rename Test (Run in Sequence)

**First request** - creates `--accent`:
```json
{
  "html": "<style>\n:root {\n  --accent: #ff0000;\n}\n.red-box {\n  background-color: var(--accent);\n}\n</style>\n<div class=\"red-box\">Red</div>"
}
```

**Second request** - creates `--accent-1` and applies it correctly:
```json
{
  "html": "<style>\n:root {\n  --accent: #00ff00;\n}\n.green-box {\n  background-color: var(--accent);\n}\n</style>\n<div class=\"green-box\">Green</div>"
}
```

### Complex: Card Component

```json
{
  "html": "<style>\n:root {\n  --card-bg: #ffffff;\n  --card-shadow: rgba(0,0,0,0.1);\n  --card-radius: 12px;\n  --card-padding: 24px;\n  --title-color: #1a1a1a;\n  --text-color: #666666;\n}\n.card {\n  background-color: var(--card-bg);\n  border-radius: var(--card-radius);\n  padding: var(--card-padding);\n}\n.card-title {\n  color: var(--title-color);\n  font-size: 24px;\n}\n.card-text {\n  color: var(--text-color);\n  font-size: 16px;\n}\n</style>\n<div class=\"card\">\n  <h2 class=\"card-title\">Card Title</h2>\n  <p class=\"card-text\">Card description text goes here.</p>\n</div>"
}
```

### Flexbox Layout

```json
{
  "html": "<style>\n:root {\n  --gap: 16px;\n  --item-bg: #f0f0f0;\n  --item-padding: 12px;\n}\n.flex-container {\n  display: flex;\n  gap: var(--gap);\n  flex-direction: row;\n}\n.flex-item {\n  background-color: var(--item-bg);\n  padding: var(--item-padding);\n}\n</style>\n<div class=\"flex-container\">\n  <div class=\"flex-item\">Item 1</div>\n  <div class=\"flex-item\">Item 2</div>\n  <div class=\"flex-item\">Item 3</div>\n</div>"
}
```

### Button Styles

```json
{
  "html": "<style>\n:root {\n  --btn-bg: #3498db;\n  --btn-color: #ffffff;\n  --btn-padding: 12px;\n  --btn-radius: 6px;\n}\n.btn {\n  background-color: var(--btn-bg);\n  color: var(--btn-color);\n  padding: var(--btn-padding);\n  border-radius: var(--btn-radius);\n}\n</style>\n<button class=\"btn\">Click Me</button>"
}
```

### Typography Scale

```json
{
  "html": "<style>\n:root {\n  --font-xs: 12px;\n  --font-sm: 14px;\n  --font-md: 16px;\n  --font-lg: 20px;\n  --font-xl: 28px;\n  --text-primary: #1a1a1a;\n  --text-secondary: #666666;\n}\n.text-xl {\n  font-size: var(--font-xl);\n  color: var(--text-primary);\n}\n.text-lg {\n  font-size: var(--font-lg);\n  color: var(--text-primary);\n}\n.text-md {\n  font-size: var(--font-md);\n  color: var(--text-secondary);\n}\n</style>\n<div>\n  <h1 class=\"text-xl\">Extra Large Heading</h1>\n  <h2 class=\"text-lg\">Large Heading</h2>\n  <p class=\"text-md\">Body text paragraph.</p>\n</div>"
}
```

### Unsupported Properties → custom_css

Properties that atomic widgets do not support are stored in the style’s `custom_css` field and rendered as-is. Use this payload to verify that `vertical-align` (and other unsupported properties) end up in `custom_css`:

```json
{
  "html": "<style>\n:root {\n  --text-color: #333333;\n  --box-padding: 12px;\n}\n.aligned-box {\n  color: var(--text-color);\n  padding: var(--box-padding);\n  vertical-align: middle;\n}\n</style>\n<div class=\"aligned-box\">Content</div>"
}
```

Expected: the widget’s style variant has supported props (e.g. `color`, `padding`) as atomic props, and `vertical-align: middle;` in `custom_css`.

---

## cURL Examples

### Basic Request

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>\n:root {\n  --primary: #ff5733;\n}\n.box {\n  color: var(--primary);\n}\n</style>\n<div class=\"box\">Hello</div>"
  }'
```

### With Post Creation Options

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>.card { padding: 20px; }</style><div class=\"card\">Content</div>",
    "postTitle": "My New Page",
    "postStatus": "draft"
  }'
```

### Insert into Existing Post

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>.new-section { margin: 20px; }</style><div class=\"new-section\">New content</div>",
    "postId": 123
  }'
```

---

## Response Format

### Success Response

```json
{
  "success": true,
  "widgets": [...],
  "imported_variables": ["--primary-color", "--spacing-md"],
  "imported_classes": {
    "card": {
      "label": "card",
      "elementor_id": "gc_abc123",
      "status": "created"
    }
  },
  "post_id": 456,
  "edit_url": "http://elementor.local/wp-admin/post.php?post=456&action=elementor"
}
```

### Error Response

```json
{
  "success": false,
  "error": "No supported HTML elements found"
}
```

### Warnings

```json
{
  "success": true,
  "widgets": [...],
  "warnings": ["Variable '--undefined-var' used but not defined"]
}
```

---

## Supported HTML Tags

- `div` -> `e-div-block`
- `h1`-`h6` -> `e-heading`
- `p` -> `e-paragraph`
- `a` -> `e-link`
- `button` -> `e-button`
- `img` -> `e-image`
- `span` -> `e-span`

---

## Architecture & API Reference

### Plugin structure

Standalone WordPress plugin (not an Elementor module). Integrates via REST API and Elementor hooks.

```
elementor-html-css-converter/
├── elementor-html-css-converter.php
├── includes/
│   ├── class-plugin.php
│   ├── class-rest-api.php
│   ├── class-css-converter.php
│   ├── class-converter-registry.php
│   ├── interfaces/
│   ├── abstracts/
│   ├── converters/
│   └── prop-types/
```

### Endpoints

| Endpoint | Purpose |
|----------|---------|
| `POST .../convert-html` | HTML + `<style>` → atomic widgets; optional variable/class import |
| `POST .../css-to-atomic` | CSS → atomic props (no document) |
| `POST .../import-classes` | CSS class definitions → Elementor Global Classes (`css` or `url`, `update_mode`: `create_new` \| `update`) |
| `POST .../import-variables` | CSS variable definitions → Elementor global variables |

### CSS variables (convert-html)

- **From HTML:** `import_variables: true` (default) extracts variables from `:root` (and other selectors) in `<style>` tags.
- **From request:** pass raw declarations in `css_variables`, e.g. `"--primary: #ff0000; --spacing: 16px;"`.
- Both can be combined; value-aware deduplication applies. Undefined `var()` references produce warnings only.

### Elementor parity

- **Style ID:** `e-{widget_id}-{7_char_hex}` (e.g. `e-d91b1ac-2e48908`).
- **Unsupported CSS:** Properties that atomic widgets do not support (e.g. `vertical-align`) are stored in the style variant’s `custom_css` field and rendered as-is.
- **Dimensions:** Padding/margin use logical properties (`block-start`, `inline-end`, `block-end`, `inline-start`). Implementation aligns with Elementor’s css-converter (PR #32856) where applicable.

---

## Documentation

- [CLAUDE.md](CLAUDE.md) - Claude Code context file
- [docs/](docs/) - Architecture overview ([ARCHITECTURE.md](docs/ARCHITECTURE.md)), plans ([docs/archive/planning/](docs/archive/planning/)), API details and Elementor parity references ([docs/archive/](docs/archive/))
