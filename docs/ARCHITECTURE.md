# Elementor HTML/CSS Converter – Architecture

## Plugin structure

Standalone WordPress plugin (not an Elementor module). Elementor’s module list is hardcoded; third-party modules cannot be registered. The plugin integrates via REST API and Elementor hooks.

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

Keep the current `includes/` layout unless the plugin gains multiple independent features (e.g. HTML parser, batch converter); then consider an internal module-style layout under `modules/`.

## REST API

| Endpoint | Purpose |
|----------|---------|
| `POST .../convert-html` | HTML + optional CSS → atomic widgets; optional variable import |
| `POST .../css-to-atomic` | CSS → atomic props (no document) |
| `POST .../import-classes` | CSS class definitions → Elementor Global Classes |
| `POST .../import-variables` | CSS variable definitions → Elementor global variables |

Detailed request/response and edge cases: see `archive/api-docs/`.

## Elementor parity

Implementation aligns with Elementor’s css-converter (PR #32856) where applicable.

- **Style ID:** `e-{widget_id}-{7_char_hex}` (e.g. `e-d91b1ac-2e48908`). Generate hex with `substr(bin2hex(random_bytes(4)), 0, 7)`.
- **Style definition:** `id`, `type: "class"`, `label: "local"`, `variants` (breakpoint/state meta + props + `custom_css`). Optional `cssName` for compatibility.
- **Dimensions:** Logical properties (`block-start`, `inline-end`, `block-end`, `inline-start`) for padding/margin.
- **Prop types:** Use Elementor prop type classes (e.g. `Color_Prop_Type::generate(...)`) for atomic props.
- **Cache:** On save, clear `_elementor_css`, `_elementor_element_cache`, `_elementor_atomic_cache_validity` and fire `elementor/atomic-widgets/styles/clear` as needed.

Full reference (property mappers, gradient shapes, widget JSON): `archive/reference/CSS-CONVERTER-PR-REFERENCE.md`. Comparison with our implementation: `archive/reference/css-converter-comparison.md`.

## CSS variables

Three ways to supply variables for conversion:

1. **`css_variables`** – Raw declarations in the request (e.g. from DB or API).
2. **`import_variables: true`** – Extract from `<style>` in the HTML.
3. **Both** – Parameter and HTML extraction combined; value-aware deduplication.

Variables are imported before conversion; undefined `var()` references produce warnings only. Details and examples: `archive/api-docs/css-variables-parameter-feature.md`.

## Further docs

- **API and feature details:** `archive/api-docs/`
- **Elementor PR / comparison:** `archive/reference/`
- **Planning and completed tasks:** `archive/planning/`, `archive/completed-tasks/`
- **Test scenarios:** `archive/tests/`

