# GitHub Reference: css-converter Module

This file contains the reference information for fetching the Elementor css-converter module from GitHub.

## PR Information

| Item | Value |
|------|-------|
| **Repository** | `elementor/elementor` |
| **PR Number** | #32856 |
| **Branch** | `hein/convert-css-to-widgets` |

## How to Fetch Files

### List all transformers (property mappers)

```bash
gh api "repos/elementor/elementor/contents/modules/atomic-widgets/styles/transformers?ref=hein/convert-css-to-widgets"
```

### Fetch a specific file

```bash
gh api "repos/elementor/elementor/contents/modules/atomic-widgets/styles/transformers/<filename>?ref=hein/convert-css-to-widgets" --jq '.content' | base64 -d
```

### Example: Fetch background-property-mapper.php

```bash
gh api "repos/elementor/elementor/contents/modules/atomic-widgets/styles/transformers/background-property-mapper.php?ref=hein/convert-css-to-widgets" --jq '.content' | base64 -d
```

## Common Transformer Files

| File | Purpose |
|------|---------|
| `background-property-mapper.php` | Background gradients, images |
| `background-color-property-mapper.php` | Background color |
| `color-property-mapper.php` | Text color |
| `padding-property-mapper.php` | Padding shorthand |
| `atomic-padding-property-mapper.php` | Individual padding properties |
| `margin-property-mapper.php` | Margin (all variants) |
| `width-property-mapper.php` | Width properties |
| `height-property-mapper.php` | Height properties |
| `flex-properties-mapper.php` | All flex properties (gap, justify-content, etc.) |
| `border-property-mapper.php` | Border shorthand |
| `border-radius-property-mapper.php` | Border radius |
| `box-shadow-property-mapper.php` | Box shadow |
| `font-weight-property-mapper.php` | Font weight |
| `text-align-property-mapper.php` | Text alignment |
| `line-height-property-mapper.php` | Line height |
| `opacity-property-mapper.php` | Opacity |
| `positioning-property-mapper.php` | top, right, bottom, left, z-index |
| `transform-property-mapper.php` | CSS transforms |

## Directory Structure in PR

```
modules/atomic-widgets/styles/
├── transformers/
│   ├── background-property-mapper.php
│   ├── background-color-property-mapper.php
│   ├── color-property-mapper.php
│   ├── padding-property-mapper.php
│   ├── ... (all property mappers)
│   └── transform-property-mapper.php
├── props/
│   ├── color-prop-type.php
│   ├── size-prop-type.php
│   ├── dimensions-prop-type.php
│   ├── ... (all prop types)
│   └── transform-prop-type.php
└── parsers/
    ├── size-value-parser.php
    └── color-value-parser.php
```

## Notes

- The `gh` CLI tool must be installed and authenticated
- Files are base64 encoded in the API response
- Use `--jq '.content' | base64 -d` to decode the content
- Always quote URLs containing `?ref=` to avoid shell glob issues
