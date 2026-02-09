# Folder Structure Refactoring Plan

## Current Structure

```
includes/
├── abstracts/
│   └── class-property-converter-base.php
├── converters/
│   ├── css/
│   ├── html/
│   └── variables/
├── interfaces/
│   ├── interface-property-converter.php
│   └── interface-widget-style-applicator.php
└── parsers/
    ├── class-color-value-parser.php
    ├── class-id-style-extractor.php
    └── class-size-value-parser.php
```

## Proposed Structure

```
includes/
└── converters/
    ├── abstracts/
    │   └── class-property-converter-base.php
    ├── interfaces/
    │   ├── interface-property-converter.php
    │   └── interface-widget-style-applicator.php
    ├── parsers/
    │   ├── class-color-value-parser.php
    │   ├── class-id-style-extractor.php
    │   └── class-size-value-parser.php
    ├── css/
    ├── html/
    └── variables/
```

## Rationale

- **Better Organization**: Abstracts, interfaces, and parsers are all related to conversion functionality
- **Logical Grouping**: All converter-related code in one place
- **Consistency**: Follows the pattern where related functionality is grouped together

## Changes Required

### 1. Move Directories

```bash
mv includes/abstracts includes/converters/
mv includes/interfaces includes/converters/
mv includes/parsers includes/converters/
```

### 2. Namespace Changes

#### Abstracts
- **File**: `includes/converters/abstracts/class-property-converter-base.php`
- **Old**: `namespace ElementorHtmlCssConverter\Abstracts;`
- **New**: `namespace ElementorHtmlCssConverter\Converters\Abstracts;`

#### Interfaces
- **File**: `includes/converters/interfaces/interface-property-converter.php`
- **Old**: `namespace ElementorHtmlCssConverter\Interfaces;`
- **New**: `namespace ElementorHtmlCssConverter\Converters\Interfaces;`

- **File**: `includes/converters/interfaces/interface-widget-style-applicator.php`
- **Old**: `namespace ElementorHtmlCssConverter\Interfaces;`
- **New**: `namespace ElementorHtmlCssConverter\Converters\Interfaces;`

#### Parsers
- **File**: `includes/converters/parsers/class-color-value-parser.php`
- **Old**: `namespace ElementorHtmlCssConverter\Parsers;`
- **New**: `namespace ElementorHtmlCssConverter\Converters\Parsers;`

- **File**: `includes/converters/parsers/class-id-style-extractor.php`
- **Old**: `namespace ElementorHtmlCssConverter\Parsers;`
- **New**: `namespace ElementorHtmlCssConverter\Converters\Parsers;`

- **File**: `includes/converters/parsers/class-size-value-parser.php`
- **Old**: `namespace ElementorHtmlCssConverter\Parsers;`
- **New**: `namespace ElementorHtmlCssConverter\Converters\Parsers;`

### 3. Use Statement Updates

All files that currently import from the old namespaces need to be updated:

#### Files Importing from Abstracts
```bash
grep -r "use ElementorHtmlCssConverter\\\\Abstracts\\\\" includes/ --include="*.php"
```
Expected files:
- All CSS converters (`includes/converters/css/class-*.php`)

**Update**:
- **Old**: `use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;`
- **New**: `use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;`

#### Files Importing from Interfaces
```bash
grep -r "use ElementorHtmlCssConverter\\\\Interfaces\\\\" includes/ --include="*.php"
```
Expected files:
- CSS converters
- Converter registries
- Possibly other core files

**Update**:
- **Old**: `use ElementorHtmlCssConverter\Interfaces\Property_Converter;`
- **New**: `use ElementorHtmlCssConverter\Converters\Interfaces\Property_Converter;`

- **Old**: `use ElementorHtmlCssConverter\Interfaces\Widget_Style_Applicator;`
- **New**: `use ElementorHtmlCssConverter\Converters\Interfaces\Widget_Style_Applicator;`

#### Files Importing from Parsers
```bash
grep -r "use ElementorHtmlCssConverter\\\\Parsers\\\\" includes/ --include="*.php"
```
Expected files:
- CSS converters (especially color and size converters)
- HTML converters

**Update**:
- **Old**: `use ElementorHtmlCssConverter\Parsers\Color_Value_Parser;`
- **New**: `use ElementorHtmlCssConverter\Converters\Parsers\Color_Value_Parser;`

- **Old**: `use ElementorHtmlCssConverter\Parsers\Size_Value_Parser;`
- **New**: `use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;`

- **Old**: `use ElementorHtmlCssConverter\Parsers\Id_Style_Extractor;`
- **New**: `use ElementorHtmlCssConverter\Converters\Parsers\Id_Style_Extractor;`

### 4. Autoloader Impact

The PSR-4 autoloader in `includes/class-autoloader.php` uses namespace-to-path mapping, so no changes are needed there. The autoloader automatically maps:
- `ElementorHtmlCssConverter\Converters\Abstracts` → `includes/converters/abstracts/`
- `ElementorHtmlCssConverter\Converters\Interfaces` → `includes/converters/interfaces/`
- `ElementorHtmlCssConverter\Converters\Parsers` → `includes/converters/parsers/`

## Implementation Steps

1. [OK] Create refactoring plan (this document)
2. Find all files that import from old namespaces
3. Move directories to new location
4. Update namespaces in moved files
5. Update use statements in all importing files
6. Test all affected functionality
7. Remove old empty directories

## Testing Checklist

After refactoring, test:
- [ ] `/css-to-atomic` endpoint - Uses parsers extensively
- [ ] `/convert-html` endpoint - Uses parsers and converters
- [ ] `/import-variables` endpoint - Check if it uses any parsers
- [ ] CSS property converters - All inherit from Property_Converter_Base
- [ ] HTML conversion - Uses Id_Style_Extractor
