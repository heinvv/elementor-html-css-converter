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

### Grid layout with templates

`display: grid` and `gap` are supported as atomic props. Grid template properties (`grid-template-columns`, `grid-template-rows`, `grid-template-areas`, etc.) have no converter and go to `custom_css`. Use these payloads to verify the split.

#### 1. Grid with template columns and rows

```json
{
  "html": "<style>\n:root {\n  --gap: 12px;\n  --cell-bg: #e8e8e8;\n  --cell-pad: 8px;\n}\n.grid {\n  display: grid;\n  gap: var(--gap);\n  grid-template-columns: 1fr 1fr 1fr;\n  grid-template-rows: auto 100px;\n}\n.cell {\n  background-color: var(--cell-bg);\n  padding: var(--cell-pad);\n}\n</style>\n<div class=\"grid\">\n  <div class=\"cell\">A</div>\n  <div class=\"cell\">B</div>\n  <div class=\"cell\">C</div>\n  <div class=\"cell\">D</div>\n  <div class=\"cell\">E</div>\n  <div class=\"cell\">F</div>\n</div>"
}
```

Expected: container has `display`, `gap` as atomic props; `grid-template-columns` and `grid-template-rows` in `custom_css`. Cells have atomic `background-color` and `padding`. Example rendered output:

```css
.elementor .grid {
    column-gap: var(--gap);
    display: grid;
    row-gap: var(--gap);
    grid-template-columns: 1fr 1fr 1fr;
    grid-template-rows: auto 100px;
}

.elementor .cell {
    padding-block-start: var(--cell-pad);
    padding-block-end: var(--cell-pad);
    padding-inline-start: var(--cell-pad);
    padding-inline-end: var(--cell-pad);
    background-color: var(--cell-bg);
}
```

#### 2. Grid with template areas (named areas)

```json
{
  "html": "<style>\n:root {\n  --gap: 8px;\n  --header-bg: #333;\n  --main-bg: #fff;\n  --aside-bg: #f5f5f5;\n  --footer-bg: #333;\n}\n.page {\n  display: grid;\n  gap: var(--gap);\n  grid-template-columns: 1fr 200px;\n  grid-template-rows: 60px 1fr 40px;\n  grid-template-areas: \"header header\" \"main aside\" \"footer footer\";\n}\n.header { grid-area: header; background-color: var(--header-bg); }\n.main { grid-area: main; background-color: var(--main-bg); }\n.aside { grid-area: aside; background-color: var(--aside-bg); }\n.footer { grid-area: footer; background-color: var(--footer-bg); }\n</style>\n<div class=\"page\">\n  <div class=\"header\">Header</div>\n  <div class=\"main\">Main</div>\n  <div class=\"aside\">Sidebar</div>\n  <div class=\"footer\">Footer</div>\n</div>"
}
```

Expected: `.page` has `display`, `gap` as atomic; `grid-template-columns`, `grid-template-rows`, `grid-template-areas` in `custom_css`. Area children have atomic `background-color` and `grid-area` in `custom_css`. Example rendered output:

```css
.elementor .page {
    column-gap: var(--gap-1);
    display: grid;
    row-gap: var(--gap-1);
    grid-template-columns: 1fr 200px;
    grid-template-rows: 60px 1fr 40px;
    grid-template-areas:
        "header header"
        "main aside"
        "footer footer";
}

.elementor .header {
    background-color: var(--header-bg);
    grid-area: header;
}

.elementor .main {
    background-color: var(--main-bg);
    grid-area: main;
}

.elementor .aside {
    background-color: var(--aside-bg);
    grid-area: aside;
}

.elementor .footer {
    background-color: var(--footer-bg);
    grid-area: footer;
}
```

#### 3. Grid with repeat() and minmax()

```json
{
  "html": "<style>\n:root {\n  --gap: 16px;\n  --card-bg: #fafafa;\n  --card-padding: 16px;\n}\n.masonry {\n  display: grid;\n  gap: var(--gap);\n  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));\n}\n.card {\n  background-color: var(--card-bg);\n  padding: var(--card-padding);\n}\n</style>\n<div class=\"masonry\">\n  <div class=\"card\">Card 1</div>\n  <div class=\"card\">Card 2</div>\n  <div class=\"card\">Card 3</div>\n</div>"
}
```

Expected: `display`, `gap` atomic; `grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));` in `custom_css`. Cards have atomic `background-color` and `padding`.

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

### Unsupported Properties / Values → custom_css

Properties that have no converter, or values that a converter rejects, are stored in the style’s `custom_css` field and rendered as-is.

**Unsupported property (no converter):** e.g. `vertical-align`, `cursor`, `outline`, `resize`, `overflow-wrap`, `list-style`.

**Unsupported value (converter returns null):** e.g. `display: table-cell` (only block/flex/grid/etc. are supported), or `text-shadow` (converter not implemented; always goes to custom_css).

**Mixed:** same rule can have both atomic props and `custom_css`; only the unsupported parts go to `custom_css`.

Single payload covering all scenarios: aligned-box (vertical-align), clickable (cursor, outline), table-cell (display: table-cell), glow (text-shadow), special (resize, overflow-wrap, list-style).

```json
{
  "html": "<style>\n:root {\n  --text-color: #333333;\n  --box-padding: 12px;\n  --bg: #f0f0f0;\n  --pad: 16px;\n  --pad-sm: 8px;\n  --color: #111;\n  --gap: 12px;\n}\n.aligned-box {\n  color: var(--text-color);\n  padding: var(--box-padding);\n  vertical-align: middle;\n}\n.clickable {\n  background-color: var(--bg);\n  padding: var(--pad);\n  cursor: pointer;\n  outline: 2px solid blue;\n}\n.table-cell {\n  padding: var(--pad-sm);\n  display: table-cell;\n}\n.glow {\n  color: var(--color);\n  text-shadow: 0 0 10px rgba(0,0,0,0.5);\n}\n.special {\n  gap: var(--gap);\n  resize: both;\n  overflow-wrap: break-word;\n  list-style: disc inside;\n}\n</style>\n<div class=\"aligned-box\">vertical-align</div>\n<div class=\"clickable\">cursor, outline</div>\n<div class=\"table-cell\">display: table-cell</div>\n<p class=\"glow\">text-shadow</p>\n<div class=\"special\">resize, overflow-wrap, list-style</div>"
}
```

Expected per class:
- **aligned-box:** atomic `color`, `padding`; `custom_css`: `vertical-align: middle;`
- **clickable:** atomic `background-color`, `padding`; `custom_css`: `cursor: pointer; outline: 2px solid blue;`
- **table-cell:** atomic `padding`; `custom_css`: `display: table-cell;`
- **glow:** atomic `color`; `custom_css`: `text-shadow: 0 0 10px rgba(0,0,0,0.5);`
- **special:** atomic `gap`; `custom_css`: `resize: both; overflow-wrap: break-word; list-style: disc inside;`

Expected output when Elementor renders (atomic props as logical properties + custom_css under `.elementor`). Property order may vary; dimensions become `padding-block-*` / `padding-inline-*`, and `gap` becomes `row-gap` and `column-gap`.

```css
.elementor .aligned-box {
    color: var(--text-color);
    padding-block-start: var(--box-padding);
    padding-block-end: var(--box-padding);
    padding-inline-start: var(--box-padding);
    padding-inline-end: var(--box-padding);
    vertical-align: middle;
}

.elementor .clickable {
    padding-block-start: var(--pad);
    padding-block-end: var(--pad);
    padding-inline-start: var(--pad);
    padding-inline-end: var(--pad);
    background-color: var(--bg);
    cursor: pointer;
    outline: 2px solid blue;
}

.elementor .table-cell {
    padding-block-start: var(--pad-sm);
    padding-block-end: var(--pad-sm);
    padding-inline-start: var(--pad-sm);
    padding-inline-end: var(--pad-sm);
    display: table-cell;
}

.elementor .glow {
    color: var(--color);
    text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

.elementor .special {
    column-gap: var(--gap);
    row-gap: var(--gap);
    resize: both;
    overflow-wrap: break-word;
    list-style: disc inside;
}
```

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
