# Elementor HTML CSS Converter

Converts HTML with CSS to Elementor atomic widgets.

## Main Endpoint

```
POST /wp-json/html-css-converter/v1/convert-html
```

See the [Endpoints](#endpoints) section below for all available endpoints.

## Important: Styling Approach

**Inline styles (`style="..."`) are NOT supported.** Styles must be defined in `<style>` tags using:

- ID selectors: `#element-id { ... }`
- Class selectors: `.class-name { ... }`

## Parameters


| Parameter          | Type    | Default            | Description                                                 |
| ------------------ | ------- | ------------------ | ----------------------------------------------------------- |
| `html`             | string  | required           | HTML content with `<style>` tags                            |
| `import_variables` | boolean | `true`             | Extract variables from `:root` in `<style>` tags            |
| `import_classes`   | boolean | `true`             | Create global classes from `.class` selectors               |
| `import_images`    | boolean | `true`             | Import external images from `<img>` tags and `background-image` CSS into WordPress media library |
| `update_mode`      | string  | `"create_new"`     | `"create_new"` or `"update"` for existing variables/classes |
| `postId`           | integer | -                  | Insert widgets into existing post                           |
| `postTitle`        | string  | `"Converted HTML"` | Title for auto-created post                                 |
| `postStatus`       | string  | `"draft"`          | Status for auto-created post                                |


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

### Image Import Example

```json
{
  "html": "<img src=\"https://example.com/logo.svg\" alt=\"Logo\">",
  "import_images": true
}
```

This will:
- Download the SVG from the external URL
- Import it into WordPress media library
- Replace the external URL with the attachment ID in the widget
- Return the imported image info in `imported_images` array

### Background Image Import Example

```json
{
  "html": "<style>\n.hero {\n  background-image: url('https://example.com/hero.jpg');\n  width: 100%;\n  height: 500px;\n}\n</style>\n<div class=\"hero\">Hero Section</div>",
  "import_images": true
}
```

Background images from CSS are also automatically imported and linked to the widget styles.

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

### Font Family Examples

The converter supports CSS `font-family` properties with Google Fonts, system fonts, fallback chains, and generic font families. CSS keywords (`inherit`, `initial`, `unset`, `revert`) are automatically skipped.

**Example: Multiple Font Family Scenarios**

```json
{
  "html": "<style>\n#google-font { font-family: \"Roboto\", Arial, sans-serif; font-size: 24px; color: #333; }\n#quoted-single { font-family: 'Open Sans'; font-size: 18px; }\n#system-font { font-family: Arial; font-size: 16px; }\n#multiple-fallbacks { font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; font-size: 20px; }\n#generic-serif { font-family: serif; font-size: 16px; }\n#generic-sans { font-family: sans-serif; font-size: 16px; }\n#generic-mono { font-family: monospace; font-size: 14px; }\n#with-other-props { font-family: \"Roboto\", sans-serif; font-size: 18px; font-weight: 600; color: #0066cc; line-height: 1.5; }\n#css-keyword { font-family: inherit; font-size: 16px; }\n#unquoted-multiword { font-family: Times New Roman, serif; font-size: 16px; }\n</style>\n<h1 id=\"google-font\">Google Font with Fallback</h1>\n<p id=\"quoted-single\">Single Quoted Font</p>\n<div id=\"system-font\">System Font (Arial)</div>\n<p id=\"multiple-fallbacks\">Multiple Fallback Fonts</p>\n<p id=\"generic-serif\">Generic Serif Font</p>\n<p id=\"generic-sans\">Generic Sans-Serif Font</p>\n<code id=\"generic-mono\">Generic Monospace Font</code>\n<div id=\"with-other-props\">Font Family with Other CSS Properties</div>\n<p id=\"css-keyword\">CSS Keyword (should skip font-family)</p>\n<p id=\"unquoted-multiword\">Unquoted Multi-word Font</p>"
}
```

**Supported formats:**

- Quoted fonts: `"Roboto"`, `'Open Sans'`
- Unquoted fonts: `Arial`, `Times New Roman`
- Fallback chains: `"Roboto", Arial, sans-serif`
- Generic families: `serif`, `sans-serif`, `monospace`, `cursive`, `fantasy`
- CSS keywords are skipped: `inherit`, `initial`, `unset`, `revert`

**Expected behavior:**

- Font-family values are converted to Elementor atomic `font-family` property using `String_Prop_Type`
- Full fallback chains are preserved (e.g., `"Roboto", Arial, sans-serif`)
- Font enqueuing is handled automatically by Elementor v4's `useStylePropResolver` hook
- CSS keywords like `inherit` are skipped (no font-family property is added)

### Responsive Breakpoints with Media Queries

The converter supports `@media (max-width: Xpx)` queries. CSS breakpoint values are automatically matched to Elementor's breakpoint system using **dynamic values from Elementor's settings** (not hardcoded). The converter reads breakpoint configurations via `Plugin::$instance->breakpoints->get_breakpoints_config()`, so it respects any custom breakpoint values you've configured in Elementor.

**Supported breakpoint formats:**

- `@media (max-width: 1024px)` → maps to Elementor `tablet` breakpoint (if tablet is set to 1024px)
- `@media (max-width: 767px)` → maps to Elementor `mobile` breakpoint (if mobile is set to 767px)
- `@media screen and (max-width: 880px)` → maps to closest Elementor breakpoint within tolerance

**Note:** The actual pixel values depend on your Elementor breakpoint settings. Default values are typically tablet: 1024px, mobile: 767px, but these can be customized in Elementor → Settings → Style → Responsive Breakpoints.

**Example: Responsive Header with ID Selectors**

```json
{
  "html": "<style>\n#header {\n  display: flex;\n  justify-content: space-between;\n  align-items: center;\n  padding: 20px 40px;\n  background-color: #ffffff;\n  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);\n}\n#logo {\n  font-size: 24px;\n  font-weight: bold;\n  color: #333333;\n}\n@media (max-width: 1024px) {\n  #header {\n    padding: 15px 30px;\n  }\n}\n@media (max-width: 767px) {\n  #header {\n    flex-direction: column;\n    gap: 20px;\n    padding: 15px 25px;\n  }\n  #logo {\n    font-size: 20px;\n  }\n}\n</style>\n<header id=\"header\">\n  <div id=\"logo\">MyBrand</div>\n  <nav>Navigation</nav>\n</header>"
}
```

**Example: Responsive Hero Section**

```json
{
  "html": "<style>\n#hero {\n  padding: 80px 20px;\n  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);\n  color: #ffffff;\n  text-align: center;\n}\n#hero-title {\n  font-size: 48px;\n  font-weight: 700;\n  margin-bottom: 20px;\n  line-height: 1.2em;\n}\n#hero-subtitle {\n  font-size: 20px;\n  margin-bottom: 30px;\n  opacity: 90%;\n}\n#hero-button {\n  padding: 15px 40px;\n  font-size: 18px;\n  font-weight: 600;\n  background-color: #ffffff;\n  color: #667eea;\n  border-radius: 5px;\n}\n@media (max-width: 1024px) {\n  #hero {\n    padding: 60px 20px;\n  }\n  #hero-title {\n    font-size: 36px;\n  }\n  #hero-subtitle {\n    font-size: 18px;\n  }\n}\n@media (max-width: 767px) {\n  #hero {\n    padding: 40px 15px;\n  }\n  #hero-title {\n    font-size: 32px;\n    margin-bottom: 15px;\n  }\n  #hero-subtitle {\n    font-size: 16px;\n    margin-bottom: 25px;\n  }\n  #hero-button {\n    width: 100%;\n    max-width: 300px;\n    font-size: 16px;\n    padding: 12px 30px;\n  }\n}\n</style>\n<div id=\"hero\">\n  <h1 id=\"hero-title\">Welcome to Our Platform</h1>\n  <p id=\"hero-subtitle\">Building amazing experiences for the modern web</p>\n  <button id=\"hero-button\">Get Started</button>\n</div>"
}
```

**Example: Responsive Grid Layout with Classes**

```json
{
  "html": "<style>\n:root {\n  --card-bg: #ffffff;\n  --card-padding: 40px;\n  --card-gap: 40px;\n}\n.features {\n  display: grid;\n  gap: var(--card-gap);\n  padding: 80px 40px;\n  background-color: #f8f9fa;\n}\n.feature-card {\n  background-color: var(--card-bg);\n  padding: var(--card-padding);\n  border-radius: 8px;\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);\n  text-align: center;\n}\n.feature-icon {\n  font-size: 48px;\n  margin-bottom: 20px;\n}\n.feature-title {\n  font-size: 24px;\n  font-weight: 600;\n  color: #333333;\n  margin-bottom: 15px;\n}\n.feature-description {\n  font-size: 16px;\n  color: #666666;\n  line-height: 1.6em;\n}\n@media (max-width: 1024px) {\n  .features {\n    gap: 35px;\n    padding: 60px 30px;\n  }\n}\n@media (max-width: 767px) {\n  .features {\n    gap: 25px;\n    padding: 40px 20px;\n  }\n  .feature-card {\n    padding: 30px 20px;\n  }\n  .feature-icon {\n    font-size: 36px;\n  }\n  .feature-title {\n    font-size: 20px;\n  }\n  .feature-description {\n    font-size: 14px;\n  }\n}\n</style>\n<div class=\"features\">\n  <div class=\"feature-card\">\n    <div class=\"feature-icon\">🚀</div>\n    <h3 class=\"feature-title\">Fast Performance</h3>\n    <p class=\"feature-description\">Optimized for speed and efficiency.</p>\n  </div>\n  <div class=\"feature-card\">\n    <div class=\"feature-icon\">🔒</div>\n    <h3 class=\"feature-title\">Secure & Safe</h3>\n    <p class=\"feature-description\">Enterprise-grade security.</p>\n  </div>\n</div>"
}
```

**How it works:**

- Desktop styles (outside `@media` queries) become the base `desktop` variant
- `@media (max-width: 1024px)` styles map to `tablet` variant
- `@media (max-width: 767px)` styles map to `mobile` variant
- Elementor generates separate CSS files per breakpoint with proper media queries
- Styles are applied automatically based on screen size

**Breakpoint matching:**

- **Exact matches**: CSS breakpoint value exactly matches an Elementor breakpoint value → returns that breakpoint name
- **Closest match**: Within 200px tolerance → returns the closest Elementor breakpoint name
- **Unmatched**: Breakpoints that don't match any Elementor breakpoint within tolerance are skipped

**Example:** If your Elementor tablet breakpoint is set to 1024px and mobile to 767px:

- `@media (max-width: 1024px)` → exact match → `tablet`
- `@media (max-width: 767px)` → exact match → `mobile`
- `@media (max-width: 880px)` → closest to mobile (113px difference) → `mobile`
- `@media (max-width: 1366px)` → no match within 200px tolerance → skipped

If you change Elementor breakpoints to tablet: 1200px and mobile: 768px, the matching will automatically use those new values.

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
  "imported_images": [
    {
      "url": "https://example.com/image.jpg",
      "id": 123
    }
  ],
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

The response may include a `warnings` array when there are non-critical issues:

```json
{
  "success": true,
  "widgets": [...],
  "warnings": [
    "Variable '--undefined-var' used but not defined",
    "SVG import requires \"Enable Unfiltered File Uploads\" to be enabled in Elementor > Settings > Advanced"
  ]
}
```

Common warnings:
- **Undefined variables**: CSS variables referenced but not defined
- **SVG import permissions**: When SVG images are detected but required permissions are missing (see [Image Import Requirements](#image-import-requirements) below)

---

## Additional Endpoints

### Apply Styles to Widget

```
POST /wp-json/html-css-converter/v1/apply-styles-to-widget
```

Applies CSS styles to an existing widget in an Elementor post by converting CSS to atomic format and merging with existing widget styles.

#### Parameters

| Parameter   | Type    | Required | Description                                    |
| ----------- | ------- | -------- | ---------------------------------------------- |
| `postId`    | integer | yes      | The Elementor post/page ID                     |
| `widgetId`  | string  | yes      | The widget ID to apply styles to               |
| `cssString` | string  | yes      | CSS styles to convert and apply                |

#### Success Response

```json
{
  "success": true,
  "postId": 123,
  "widgetId": "abc123",
  "stylesApplied": {
    "color": "#ff0000",
    "padding": "16px"
  },
  "customCss": "vertical-align: middle;"
}
```

#### Error Response

```json
{
  "success": false,
  "error": "Widget not found"
}
```

#### Example Request

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/apply-styles-to-widget" \
  -H "Content-Type: application/json" \
  -d '{
    "postId": 123,
    "widgetId": "abc123",
    "cssString": "#my-widget { color: #ff0000; padding: 20px; background-color: #f0f0f0; }"
  }'
```

---

### Create Post with Widget

```
POST /wp-json/html-css-converter/v1/create-post-with-widget
```

Creates a new Elementor post/page with a single styled widget.

#### Parameters

| Parameter       | Type    | Required | Default      | Description                                    |
| --------------- | ------- | -------- | ------------ | ---------------------------------------------- |
| `postTitle`     | string  | yes      | -            | Title for the new post                         |
| `postStatus`    | string  | no       | `"draft"`    | Post status (`"draft"`, `"publish"`, etc.)     |
| `widgetType`    | string  | yes      | -            | Elementor widget type (e.g., `"e-heading"`)    |
| `widgetSettings` | object  | no       | `{}`         | Widget settings/configuration                   |
| `cssString`     | string  | no       | `""`         | CSS styles to convert and apply to the widget  |

#### Success Response

```json
{
  "success": true,
  "postId": 456,
  "widgetId": "def456",
  "editUrl": "http://elementor.local/wp-admin/post.php?post=456&action=elementor"
}
```

#### Error Response

```json
{
  "success": false,
  "error": "Failed to create post"
}
```

#### Example Request

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/create-post-with-widget" \
  -H "Content-Type: application/json" \
  -d '{
    "postTitle": "Styled Heading Page",
    "postStatus": "draft",
    "widgetType": "e-heading",
    "widgetSettings": {
      "title": "Hello World",
      "size": "large"
    },
    "cssString": ".e-heading { color: #0066cc; font-size: 32px; margin-bottom: 20px; }"
  }'
```

---

### Add Widget to Post

```
POST /wp-json/html-css-converter/v1/add-widget-to-post
```

Adds a styled widget to an existing Elementor post/page.

#### Parameters

| Parameter       | Type    | Required | Default  | Description                                    |
| --------------- | ------- | -------- | -------- | ---------------------------------------------- |
| `postId`        | integer | yes      | -        | The Elementor post/page ID                     |
| `widgetType`    | string  | yes      | -        | Elementor widget type (e.g., `"e-heading"`)   |
| `widgetSettings` | object  | no       | `{}`     | Widget settings/configuration                   |
| `cssString`     | string  | no       | `""`     | CSS styles to convert and apply to the widget  |

#### Success Response

```json
{
  "success": true,
  "postId": 123,
  "widgetId": "ghi789"
}
```

#### Error Response

```json
{
  "success": false,
  "error": "Failed to add widget to post"
}
```

#### Example Request

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/add-widget-to-post" \
  -H "Content-Type: application/json" \
  -d '{
    "postId": 123,
    "widgetType": "e-button",
    "widgetSettings": {
      "text": "Click Me",
      "link": {
        "url": "https://example.com"
      }
    },
    "cssString": ".e-button { background-color: #3498db; color: #ffffff; padding: 12px 24px; border-radius: 6px; }"
  }'
```

---

### CSS to Atomic

```
POST /wp-json/html-css-converter/v1/css-to-atomic
```

Converts CSS string to atomic widget properties without creating or modifying any documents.

#### Parameters

| Parameter  | Type   | Required | Description                    |
| ---------- | ------ | -------- | ------------------------------ |
| `cssString` | string | yes      | CSS styles to convert          |

#### Success Response

```json
{
  "success": true,
  "props": {
    "color": "#ff0000",
    "padding": "16px"
  },
  "customCss": "vertical-align: middle;"
}
```

#### Example Request

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/css-to-atomic" \
  -H "Content-Type: application/json" \
  -d '{
    "cssString": ".my-class { color: #ff0000; padding: 16px 24px; background-color: #f0f0f0; vertical-align: middle; }"
  }'
```

---

### Import Variables

```
POST /wp-json/html-css-converter/v1/import-variables
```

Imports CSS variables from CSS string or URL into Elementor global variables.

#### Parameters

| Parameter    | Type   | Required | Default        | Description                                                      |
| ------------ | ------ | -------- | -------------- | ---------------------------------------------------------------- |
| `css`        | string | no*      | -              | CSS string containing variable definitions                       |
| `url`        | string | no*      | -              | URL to fetch CSS from                                            |
| `update_mode` | string | no       | `"create_new"` | `"create_new"` or `"update"` for existing variables             |

\* Either `css` or `url` must be provided.

#### Success Response

```json
{
  "success": true,
  "variables": {
    "primary-color": {
      "name": "--primary-color",
      "value": "#ff0000",
      "type": "color-hex"
    }
  },
  "created": 5,
  "reused": 2,
  "updated": 0,
  "reactivated": 0,
  "skipped_variables": [
    {
      "name": "--transition-speed",
      "value": "0.3s"
    }
  ]
}
```

When some variables cannot be imported (unsupported value types), they are listed in `skipped_variables` so you can see exactly what was not imported.

#### Error Response

```json
{
  "error": "No variables found in CSS",
  "code": "no_variables"
}
```

#### Example Request (CSS String)

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": ":root { --primary-color: #ff5733; --spacing-md: 16px; --spacing-lg: 32px; --font-size-base: 16px; }",
    "update_mode": "create_new"
  }'
```

#### Example Request (URL)

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com/styles.css",
    "update_mode": "update"
  }'
```

#### Supported Variable Types

Variables are imported based on their CSS value format. The following value types are recognised and converted to Elementor global variables:

| Category | Supported formats | Examples |
| -------- | ----------------- | -------- |
| **Colors** | Hex, RGB, RGBA, HSL, HSLA | `#ff0000`, `rgb(255,0,0)`, `rgba(255,0,0,0.5)`, `hsl(0,100%,50%)`, `hsla(0,100%,50%,0.5)` |
| **Colors** | Named colors | `red`, `dodgerblue`, `transparent` |
| **Colors** | `color-mix()` function | `color-mix(in srgb, #fff 50%, #000)` |
| **Sizes** | Viewport and length units | `16px`, `1rem`, `1.5em`, `50vw`, `100vh`, `5ch`, `10vmin`, `20vmax` |
| **Sizes** | Percentages | `50%`, `100%` |
| **Sizes** | CSS math functions | `calc(100% - 40px)`, `min(100vw, 1200px)`, `max(50vw, 300px)`, `clamp(1rem, 2.5vw, 2rem)` |
| **Sizes** | Opacity (name contains "opacity", value 0–1) | `--opacity-dim: 0.5` |
| **Sizes** | Line height (name contains "line-height" or "lineheight" unitless) | `--line-height-base: 1.6` |
| **Fonts** | Font family values | `'Inter', sans-serif`, `Arial`, `monospace` |

#### Unsupported Variable Types

Variables whose values do not match any of the supported formats above are **not imported**. The import response includes a `skipped_variables` array listing each variable that was not imported (name and value). Common unsupported value types:

| Unsupported type | Examples |
| ---------------- | -------- |
| Time and duration | `0.3s`, `300ms` |
| Unsupported length units | `12pt` |
| Shorthand values | `1px solid #ccc`, `0 2px 4px rgba(0,0,0,0.1)` |
| CSS functions (non-color, non-math) | `var(--other)`, `linear-gradient(...)`, `url(...)` |
| Unitless numbers (non-opacity, non–line-height) | `--z-index: 10`, `--scale: 2` |
| CSS keywords | `inherit`, `initial`, `unset`, `revert` |

---

### Import Classes

```
POST /wp-json/html-css-converter/v1/import-classes
```

Imports CSS class definitions from CSS string or URL into Elementor Global Classes.

#### Parameters

| Parameter    | Type   | Required | Default        | Description                                                      |
| ------------ | ------ | -------- | -------------- | ---------------------------------------------------------------- |
| `css`        | string | no*      | -              | CSS string containing class definitions                          |
| `url`        | string | no*      | -              | URL to fetch CSS from                                            |
| `update_mode` | string | no       | `"create_new"` | `"create_new"` or `"update"` for existing classes                |
| `context`    | string | no       | `"frontend"`   | `"frontend"` or `"preview"`                                      |

\* Either `css` or `url` must be provided.

#### Success Response

```json
{
  "success": true,
  "classes": {
    "card": {
      "label": "card",
      "elementor_id": "gc_abc123",
      "status": "created"
    }
  },
  "statistics": {
    "detected": 10,
    "converted": 8,
    "registered": 8,
    "skipped": 2,
    "updated": 0
  },
  "overflow": []
}
```

#### Error Response

```json
{
  "error": "No classes found in CSS",
  "code": "no_classes"
}
```

#### Example Request (CSS String)

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/import-classes" \
  -H "Content-Type: application/json" \
  -d '{
    "css": ".card { background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); } .btn-primary { background-color: #3498db; color: #ffffff; padding: 12px 24px; border-radius: 6px; }",
    "update_mode": "create_new",
    "context": "frontend"
  }'
```

#### Example Request (URL)

```bash
curl -X POST "http://elementor.local/wp-json/html-css-converter/v1/import-classes" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com/components.css",
    "update_mode": "update",
    "context": "preview"
  }'
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


| Endpoint                    | Purpose                                                                                                   |
| --------------------------- | --------------------------------------------------------------------------------------------------------- |
| `POST .../convert-html`     | HTML + `<style>` → atomic widgets; optional variable/class import                                         |
| `POST .../css-to-atomic`    | CSS → atomic props (no document)                                                                          |
| `POST .../apply-styles-to-widget` | Apply CSS styles to an existing widget in a post                                                          |
| `POST .../create-post-with-widget` | Create a new Elementor post with a styled widget                                                         |
| `POST .../add-widget-to-post` | Add a styled widget to an existing Elementor post                                                         |
| `POST .../import-classes`   | CSS class definitions → Elementor Global Classes (`css` or `url`, `update_mode`: `create_new` | `update`) |
| `POST .../import-variables` | CSS variable definitions → Elementor global variables                                                     |


### CSS variables (convert-html)

- **From HTML:** `import_variables: true` (default) extracts variables from `:root` (and other selectors) in `<style>` tags.
- **From request:** pass raw declarations in `css_variables`, e.g. `"--primary: #ff0000; --spacing: 16px;"`.
- Both can be combined; value-aware deduplication applies. Undefined `var()` references produce warnings only.

### Image Import

When `import_images: true` (default), external images from `<img>` tags and `background-image` CSS properties are automatically imported into the WordPress media library.

#### Image Import Features

- **Automatic import**: External images are downloaded and added to the media library
- **Duplicate detection**: Checks for existing images by:
  - URL (if already a local attachment)
  - Elementor source hash (SHA1 of source URL)
  - Filename + file size match
- **Widget data update**: Image URLs are replaced with WordPress attachment IDs in widget settings
- **Response data**: Returns `imported_images` array with imported image URLs and attachment IDs

#### Image Import Requirements

**Regular images (JPG, PNG, GIF, WebP, etc.):**
- No special requirements - WordPress handles these by default

**SVG images:**
- **Elementor setting**: "Enable Unfiltered File Uploads" must be enabled in Elementor > Settings > Advanced
- **PHP classes**: `DOMDocument` and `SimpleXMLElement` must be available (usually included)
- **User capability**: User must have `manage_options` capability OR Elementor role manager must allow JSON uploads
- **Mime type**: SVG mime type (`image/svg+xml`) must be registered in WordPress `upload_mimes` filter

If SVG import requirements are not met, warnings will be included in the API response. Regular images will still import successfully.

#### Security Bypass Handler for Unauthenticated REST API

The plugin includes a security bypass handler (`Svg_Security_Bypass_Handler`) that allows SVG imports to work in unauthenticated REST API contexts while still respecting Elementor's security settings.

**How it works:**

When the REST API endpoint is called without authentication (user ID = 0), the bypass handler:

1. **Checks Elementor option directly**: Instead of requiring user context, it checks the `elementor_unfiltered_files_upload` option directly from the database
2. **Registers SVG mime type**: Automatically registers the SVG mime type for REST API requests if Safe SVG plugin is active or if the option is enabled
3. **Allows operations conditionally**: Only allows SVG imports when:
   - Elementor "Enable Unfiltered File Uploads" setting is enabled
   - SVG sanitizer can run (DOMDocument/SimpleXMLElement available)
   - The request is a REST API request

**Security considerations:**

- The bypass handler does NOT override WordPress or Elementor security hooks
- It only applies to unauthenticated REST API requests
- It still requires Elementor's unfiltered uploads setting to be enabled
- SVG content is still sanitized using Elementor's sanitizer
- All security checks are centralized in `class-svg-security-bypass-handler.php` for easy maintenance

**Configuration:**

To enable SVG imports for unauthenticated REST API requests:

1. Enable "Enable Unfiltered File Uploads" in Elementor > Settings > Advanced
2. Ensure Safe SVG plugin is active (recommended) OR add SVG mime type to your theme's `functions.php`:
   ```php
   add_filter( 'upload_mimes', function($mimes) {
       $mimes['svg'] = 'image/svg+xml';
       return $mimes;
   } );
   ```

The bypass handler logic can be updated independently in `includes/converters/images/class-svg-security-bypass-handler.php` without modifying the main import service code.

#### Example: Image Import

```json
{
  "html": "<img src=\"https://example.com/image.jpg\" alt=\"Example\">",
  "import_images": true
}
```

Response includes imported images:

```json
{
  "success": true,
  "widgets": [...],
  "imported_images": [
    {
      "url": "https://example.com/image.jpg",
      "id": 123
    }
  ]
}
```

### Elementor parity

- **Style ID:** `e-{widget_id}-{7_char_hex}` (e.g. `e-d91b1ac-2e48908`).
- **Unsupported CSS:** Properties that atomic widgets do not support (e.g. `vertical-align`) are stored in the style variant’s `custom_css` field and rendered as-is.
- **Dimensions:** Padding/margin use logical properties (`block-start`, `inline-end`, `block-end`, `inline-start`). Implementation aligns with Elementor’s css-converter (PR #32856) where applicable.

---

## Documentation

- [CLAUDE.md](CLAUDE.md) - Claude Code context file
- [docs/](docs/) - Architecture overview ([ARCHITECTURE.md](docs/ARCHITECTURE.md)), plans ([docs/archive/planning/](docs/archive/planning/)), API details and Elementor parity references ([docs/archive/](docs/archive/))

