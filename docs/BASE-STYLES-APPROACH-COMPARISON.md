# Base Styles Removal: Implementation

## How It Works

Base style classes (e.g. `e-div-block-base`, `e-paragraph-base`) are removed via two independent mechanisms targeting frontend rendering and editor preview.

### Frontend (PHP Server-Side)

Two hooks intercept the Elementor rendering pipeline:

**1. `elementor/element/after_add_attributes`**
- Fires after `add_render_attributes()` on every element
- Checks if the element is a converter widget via `is_converter_element()`
- Calls `remove_render_attribute('_wrapper', 'class', ...)` to strip base classes
- Handles: div-block, flexbox, form (PHP-rendered wrappers)

**2. `elementor/widget/render_content`**
- Filters widget inner HTML before output
- Strips base class names from Twig template output via regex
- Handles: paragraph, heading, button, image, svg, divider, youtube (Twig-rendered)

### Editor (JavaScript Client-Side)

**`assets/js/editor/base-styles-override.js`**
- Overrides `elementor.helpers.getAtomicWidgetBaseStyles()` to return `{}` for converter widgets
- Clears htmlCache and triggers re-render on document load
- DOM cleanup for any base classes that slip through
- Preview reload after all re-renders complete

## Detection Logic

`is_converter_element()` uses two detection methods:

1. **Primary**: Check `editor_settings['css_converter_widget']` or `editor_settings['disable_base_styles']`
   - Works on initial load after conversion
   - Gets stripped by Elementor `parse_editor_settings()` on first editor save

2. **Fallback**: Check for converter class patterns matching `e-[hex]-[hex]` format
   - Works always, including after editor save
   - Uses `get_atomic_settings()['classes']` to find converter-specific class names

## Where Base Classes Originate

| Widget Type | Base Class Source | Removal Hook |
|-------------|-------------------|--------------|
| `e-div-block` | `add_render_attributes()` adds to `_wrapper` | `after_add_attributes` |
| `e-flexbox` | `add_render_attributes()` adds to `_wrapper` | `after_add_attributes` |
| `e-form` | `add_render_attributes()` adds to `_wrapper` | `after_add_attributes` |
| `e-paragraph` | Twig template uses `base_styles.base` | `render_content` |
| `e-heading` | Twig template uses `base_styles.base` | `render_content` |
| `e-button` | Twig template uses `base_styles.base` | `render_content` |
| `e-image` | Twig template uses `base_styles.base` | `render_content` |
| `e-svg` | Render method uses `base_styles_dictionary` | `render_content` |
| `e-divider` | Twig template uses `base_styles.base` | `render_content` |
| `e-youtube` | Twig template uses `base_styles.base` | `render_content` |

## Files

| File | Purpose |
|------|---------|
| `includes/plugin.php` | PHP hooks for frontend rendering |
| `assets/js/editor/base-styles-override.js` | JS override for editor preview |
| `includes/converters/html/class-atomic-widget-json-creator.php` | Sets `editor_settings` flags on converted widgets |
| `includes/converters/html/class-html-converter.php` | Sets `editor_settings` flags on wrapper containers |
