# Test Fixtures

Fixture files for PHPUnit integration and unit tests. Load via `Fixture_Loader::load_json()`.

## Structure

```
fixtures/
├── integration/     Conversion payloads for full HTML+CSS tests
├── classes/         Class selector fixtures
├── variables/       :root CSS variable fixtures
└── breakpoints/     Media query fixtures
```

## Integration Fixtures

| File | Keys | Purpose |
|------|------|---------|
| `full-import-payload.json` | `html`, `import_variables`, `import_classes`, `update_mode` | Full conversion with IDs, classes, :root, media queries |
| `responsive-hero.json` | `html` | Simple responsive hero with breakpoints |
| `hero-id-simple.json` | `html` | ID selector with padding only |
| `hero-responsive-breakpoints.json` | `html` | ID selector with desktop, tablet, mobile breakpoints |

## Variables Fixtures

| File | Keys | Purpose |
|------|------|---------|
| `root-simple.json` | `css` | Basic :root variables |
| `root-multiple-types.json` | `css` | Mixed variable types |

## Classes Fixtures

| File | Keys | Purpose |
|------|------|---------|
| `single-class-simple.json` | `html` | Single class, desktop only |
| `breakpoint-desktop-tablet-mobile.json` | `html` | Class with responsive variants |

## Breakpoints Fixtures

| File | Keys | Purpose |
|------|------|---------|
| `media-max-width-exact.json` | `css` | Exact Elementor breakpoint match |
| `media-max-width-within-tolerance.json` | `css` | Within 200px tolerance |
| `media-multiple-breakpoints.json` | `css` | Multiple breakpoints |
| `unmatched-breakpoint-skipped.json` | `css` | Unmatched media (2000px) |
| `id-rules-per-breakpoint.json` | `css` | ID rules with breakpoints |
