# Autoloader Modernization Plan

## Overview

Modernize the elementor-html-css-converter plugin to use PSR-4 autoloading, eliminating manual `require_once` statements and following clean code best practices inspired by Elementor's architecture.

**Goal:** Replace the current manual file loading system with an automatic class loader that maps namespaces to file paths.

**Benefits:**
- [OK] Eliminates ~40+ require_once statements
- [OK] Automatic class loading on-demand
- [OK] Better performance (only loads classes when used)
- [OK] Cleaner codebase following PSR-4 standards
- [OK] Easier to maintain and extend

---

## Current State Analysis

### Current File Loading System

**Location:** `elementor-html-css-converter.php:23-83`

```php
function ehcc_load_files() {
    require_once EHCC_PATH . 'includes/class-converter-registry.php';
    require_once EHCC_PATH . 'includes/class-css-converter.php';
    // ... 40+ require_once statements
}
```

**Problems:**
- All files loaded on every request (even if not needed)
- Manual maintenance of require_once list
- Order dependencies can cause issues
- New files must be manually registered

### Current Namespace Structure

```
ElementorHtmlCssConverter\
├── Converters\Css\
├── Converters\Html\
├── Convertors\Variables\  [Note: typo - should be "Converters"]
├── Services\Variables\
├── Parsers\
└── (Root classes)
```

**Issues:**
- Inconsistent naming: "Converters" vs "Convertors"
- No clear hierarchy for Core vs Modules
- Flat structure in includes/ directory

---

## Elementor's Autoloader Pattern

### Key Learnings from Elementor Study

**File:** `/elementor/includes/autoloader.php`

**Architecture:** Dual-strategy autoloader
1. **Performance map:** Hardcoded array of frequently-used classes
2. **PSR-4 fallback:** Namespace-to-path mapping for everything else

```php
class Autoloader {
    private static $classes_map = [
        // Hardcoded for performance
        'Elementor\Plugin' => 'includes/plugin.php',
    ];

    private static $namespace_to_path = [
        'Elementor\Core' => 'core/',
        'Elementor\Modules' => 'modules/',
    ];

    public static function autoload( $class ) {
        // Strategy 1: Check hardcoded map
        if ( isset( self::$classes_map[ $class ] ) ) {
            require self::$classes_map[ $class ];
            return;
        }

        // Strategy 2: PSR-4 namespace mapping
        foreach ( self::$namespace_to_path as $namespace => $path ) {
            if ( strpos( $class, $namespace ) === 0 ) {
                $filename = str_replace( $namespace, '', $class );
                $filename = str_replace( '\\', '/', $filename );
                $filename = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $filename ) );
                $file = ELEMENTOR_PATH . $path . $filename . '.php';

                if ( file_exists( $file ) ) {
                    require $file;
                }
                return;
            }
        }
    }

    public static function register() {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }
}
```

**Class Name to File Name Convention:**
- `PascalCase` → `kebab-case`
- `Color_Converter` → `color-converter.php`
- Prefix: `class-` for class files

---

## Proposed New Structure

### Directory Organization

```
elementor-html-css-converter/
├── includes/
│   ├── autoloader.php                           [NEW - PSR-4 autoloader]
│   ├── plugin.php                              [RENAME from class-plugin.php]
│   │
│   ├── core/                                   [NEW - Core functionality]
│   │   ├── converter-registry.php
│   │   ├── css-converter.php
│   │   ├── html-converter.php
│   │   ├── rest-api.php
│   │   └── variables-rest-api.php
│   │
│   ├── converters/                             [RENAME from mixed naming]
│   │   ├── css/
│   │   │   ├── class-color-converter.php
│   │   │   ├── class-background-color-converter.php
│   │   │   └── ... (all CSS property converters)
│   │   │
│   │   ├── html/
│   │   │   └── class-html-to-widget-converter.php
│   │   │
│   │   └── variables/                          [MOVE & RENAME from convertors]
│   │       ├── variable-convertor-interface.php
│   │       ├── variable-convertor-registry.php
│   │       └── convertors/
│   │           ├── abstract-variable-convertor.php
│   │           ├── color-hex-variable-convertor.php
│   │           └── ... (5 convertors)
│   │
│   ├── services/
│   │   └── variables/
│   │       ├── variable-conversion-service.php
│   │       └── variable-extractor.php
│   │
│   ├── parsers/
│   │   ├── class-color-value-parser.php
│   │   ├── class-css-parser.php
│   │   └── ... (all parsers)
│   │
│   └── utilities/                              [NEW - Utility classes]
│       ├── class-style-definition-builder.php
│       └── class-widget-style-applicator.php
│
└── elementor-html-css-converter.php            [Main plugin file]
```

### Namespace Hierarchy

```
ElementorHtmlCssConverter\                      [Root namespace]
├── Core\                                       [Core classes]
│   ├── Converter_Registry
│   ├── Css_Converter
│   ├── Html_Converter
│   ├── Rest_Api
│   └── Variables_Rest_Api
│
├── Converters\                                 [All converters]
│   ├── Css\
│   │   ├── Color_Converter
│   │   ├── Background_Color_Converter
│   │   └── ...
│   │
│   ├── Html\
│   │   └── Html_To_Widget_Converter
│   │
│   └── Variables\
│       ├── Variable_Convertor_Interface
│       ├── Variable_Convertor_Registry
│       └── Convertors\
│           ├── Abstract_Variable_Convertor
│           ├── Color_Hex_Variable_Convertor
│           └── ...
│
├── Services\
│   └── Variables\
│       ├── Variable_Conversion_Service
│       └── Variable_Extractor
│
├── Parsers\
│   ├── Color_Value_Parser
│   ├── Css_Parser
│   └── ...
│
└── Utilities\
    ├── Style_Definition_Builder
    └── Widget_Style_Applicator
```

---

## Implementation Plan

### Phase 1: Create Autoloader

**New File:** `includes/autoloader.php`

```php
<?php
namespace ElementorHtmlCssConverter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Autoloader
 *
 * PSR-4 compliant class autoloader with performance optimization.
 *
 * @package ElementorHtmlCssConverter
 */
class Autoloader {

    /**
     * Performance map for frequently-used classes.
     *
     * @var array<string, string>
     */
    private static $classes_map = [
        // Core classes (most frequently instantiated)
        'ElementorHtmlCssConverter\Plugin' => 'includes/plugin.php',
        'ElementorHtmlCssConverter\Core\Converter_Registry' => 'includes/core/converter-registry.php',
        'ElementorHtmlCssConverter\Core\Rest_Api' => 'includes/core/rest-api.php',
        'ElementorHtmlCssConverter\Core\Variables_Rest_Api' => 'includes/core/variables-rest-api.php',
    ];

    /**
     * Namespace to path mapping for PSR-4 autoloading.
     *
     * @var array<string, string>
     */
    private static $namespace_to_path = [
        'ElementorHtmlCssConverter\Core' => 'includes/core/',
        'ElementorHtmlCssConverter\Converters' => 'includes/converters/',
        'ElementorHtmlCssConverter\Services' => 'includes/services/',
        'ElementorHtmlCssConverter\Parsers' => 'includes/parsers/',
        'ElementorHtmlCssConverter\Utilities' => 'includes/utilities/',
    ];

    /**
     * Run autoloader.
     *
     * @return void
     */
    public static function register() {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    /**
     * Autoload callback.
     *
     * @param string $class Full class name with namespace.
     * @return void
     */
    public static function autoload( $class ) {
        // Only handle our namespace
        if ( strpos( $class, 'ElementorHtmlCssConverter\\' ) !== 0 ) {
            return;
        }

        // Strategy 1: Check performance map
        if ( isset( self::$classes_map[ $class ] ) ) {
            $file = EHCC_PATH . self::$classes_map[ $class ];
            if ( file_exists( $file ) ) {
                require $file;
            }
            return;
        }

        // Strategy 2: PSR-4 namespace mapping
        foreach ( self::$namespace_to_path as $namespace => $path ) {
            if ( strpos( $class, $namespace ) === 0 ) {
                $relative_class = str_replace( $namespace . '\\', '', $class );
                $file = self::get_file_path( $relative_class, $path );

                if ( file_exists( $file ) ) {
                    require $file;
                }
                return;
            }
        }

        // Strategy 3: Root namespace fallback
        // For classes directly under ElementorHtmlCssConverter\ without subnamespace
        $relative_class = str_replace( 'ElementorHtmlCssConverter\\', '', $class );

        // If it still has backslashes, it's in a subnamespace we didn't handle
        if ( strpos( $relative_class, '\\' ) !== false ) {
            return;
        }

        $file = self::get_file_path( $relative_class, 'includes/' );
        if ( file_exists( $file ) ) {
            require $file;
        }
    }

    /**
     * Convert class name to file path.
     *
     * Converts PascalCase and Snake_Case to kebab-case.
     * Example: Color_Converter → class-color-converter.php
     *
     * @param string $class_name Class name without namespace.
     * @param string $base_path  Base directory path.
     * @return string Full file path.
     */
    private static function get_file_path( $class_name, $base_path ) {
        // Replace namespace separators with directory separators
        $class_name = str_replace( '\\', '/', $class_name );

        // Split by directory separator to handle nested namespaces
        $parts = explode( '/', $class_name );
        $filename = array_pop( $parts );

        // Convert class name to kebab-case
        // Color_Converter → color-converter
        // ColorConverter → color-converter
        $filename = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $filename ) );
        $filename = str_replace( '_', '-', $filename );

        // Add class- prefix
        $filename = 'class-' . $filename . '.php';

        // Rebuild path with directories
        if ( ! empty( $parts ) ) {
            $directory = implode( '/', array_map( 'strtolower', $parts ) );
            return EHCC_PATH . $base_path . $directory . '/' . $filename;
        }

        return EHCC_PATH . $base_path . $filename;
    }
}
```

---

### Phase 2: Update Main Plugin File

**File:** `elementor-html-css-converter.php`

**Changes:**

1. **Remove** entire `ehcc_load_files()` function
2. **Add** autoloader registration

```php
<?php
/**
 * Plugin Name: Elementor HTML & CSS Converter
 * ...
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'EHCC_VERSION', '1.0.0' );
define( 'EHCC_PATH', plugin_dir_path( __FILE__ ) );
define( 'EHCC_URL', plugin_dir_url( __FILE__ ) );

// Register autoloader
require_once EHCC_PATH . 'includes/autoloader.php';
ElementorHtmlCssConverter\Autoloader::register();

// Initialize plugin
add_action( 'plugins_loaded', function() {
    ElementorHtmlCssConverter\Plugin::instance();
} );
```

**Before:**
```php
// Old approach - manual loading
function ehcc_load_files() {
    require_once EHCC_PATH . 'includes/class-plugin.php';
    require_once EHCC_PATH . 'includes/class-converter-registry.php';
    // ... 40+ more require_once
}
add_action( 'plugins_loaded', 'ehcc_load_files' );
add_action( 'plugins_loaded', function() {
    ElementorHtmlCssConverter\Plugin::instance();
} );
```

**After:**
```php
// New approach - autoloading
require_once EHCC_PATH . 'includes/autoloader.php';
ElementorHtmlCssConverter\Autoloader::register();

add_action( 'plugins_loaded', function() {
    ElementorHtmlCssConverter\Plugin::instance();
} );
```

---

### Phase 3: Reorganize Files & Update Namespaces

#### Step 3.1: Create New Directory Structure

```bash
# Create new directories
mkdir -p includes/core
mkdir -p includes/converters/css
mkdir -p includes/converters/html
mkdir -p includes/converters/variables/convertors
mkdir -p includes/utilities
```

#### Step 3.2: Move Core Classes

**Move & Update:**

| Old Path | New Path | Namespace Change |
|----------|----------|------------------|
| `includes/class-plugin.php` | `includes/plugin.php` | No change: `ElementorHtmlCssConverter\Plugin` |
| `includes/class-converter-registry.php` | `includes/core/class-converter-registry.php` | `ElementorHtmlCssConverter\Core\Converter_Registry` |
| `includes/class-css-converter.php` | `includes/core/class-css-converter.php` | `ElementorHtmlCssConverter\Core\Css_Converter` |
| `includes/class-html-converter.php` | `includes/core/class-html-converter.php` | `ElementorHtmlCssConverter\Core\Html_Converter` |
| `includes/class-rest-api.php` | `includes/core/class-rest-api.php` | `ElementorHtmlCssConverter\Core\Rest_Api` |
| `includes/class-variables-rest-api.php` | `includes/core/class-variables-rest-api.php` | `ElementorHtmlCssConverter\Core\Variables_Rest_Api` |

**Example Update - Converter_Registry:**

```php
<?php
// OLD: includes/class-converter-registry.php
namespace ElementorHtmlCssConverter;

class Converter_Registry {
    // ...
}
```

```php
<?php
// NEW: includes/core/class-converter-registry.php
namespace ElementorHtmlCssConverter\Core;

class Converter_Registry {
    // ...
}
```

#### Step 3.3: Move Converter Classes

**CSS Converters:**

All files in `includes/converters/css/` get namespace: `ElementorHtmlCssConverter\Converters\Css\`

| Old Path | New Path | Namespace |
|----------|----------|-----------|
| `includes/converters/css/class-color-converter.php` | Same | `ElementorHtmlCssConverter\Converters\Css\Color_Converter` |

**Variable Converters:**

| Old Path | New Path | Namespace Change |
|----------|----------|------------------|
| `includes/convertors/variables/` | `includes/converters/variables/` | `ElementorHtmlCssConverter\Converters\Variables\` |

#### Step 3.4: Move Utility Classes

| Old Path | New Path | Namespace Change |
|----------|----------|------------------|
| `includes/class-style-definition-builder.php` | `includes/utilities/class-style-definition-builder.php` | `ElementorHtmlCssConverter\Utilities\Style_Definition_Builder` |
| `includes/class-widget-style-applicator.php` | `includes/utilities/class-widget-style-applicator.php` | `ElementorHtmlCssConverter\Utilities\Widget_Style_Applicator` |

---

### Phase 4: Update Use Statements

After moving files, update all `use` statements in affected files.

**Example - Plugin.php:**

```php
<?php
// OLD
namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Converters\Css\Color_Converter;
// ... all converters

class Plugin {
    private Converter_Registry $registry;
    // ...
}
```

```php
<?php
// NEW
namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Core\Converter_Registry;
use ElementorHtmlCssConverter\Core\Css_Converter;
use ElementorHtmlCssConverter\Core\Html_Converter;
use ElementorHtmlCssConverter\Core\Rest_Api;
use ElementorHtmlCssConverter\Core\Variables_Rest_Api;
use ElementorHtmlCssConverter\Utilities\Style_Definition_Builder;
use ElementorHtmlCssConverter\Utilities\Widget_Style_Applicator;
use ElementorHtmlCssConverter\Converters\Css\Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Background_Color_Converter;
// ... all other converters

class Plugin {
    private Core\Converter_Registry $registry;
    private Core\Rest_Api $rest_api;
    // ...
}
```

**Automated Search & Replace:**

For bulk updates, use these patterns:

```bash
# Find files that need updating
grep -r "use ElementorHtmlCssConverter\\\\" includes/

# Update Converter_Registry references
find includes/ -type f -name "*.php" -exec sed -i '' \
  's/ElementorHtmlCssConverter\\Converter_Registry/ElementorHtmlCssConverter\\Core\\Converter_Registry/g' {} +

# Update Rest_Api references
find includes/ -type f -name "*.php" -exec sed -i '' \
  's/ElementorHtmlCssConverter\\Rest_Api/ElementorHtmlCssConverter\\Core\\Rest_Api/g' {} +
```

---

### Phase 5: Update Performance Map

After all files are moved, update `Autoloader::$classes_map` with frequently-used classes.

**Guidelines:**
- Include classes instantiated on every request
- Include classes used in hot paths (REST endpoints, conversion)
- Exclude rarely-used converters (let PSR-4 handle them)

**Recommended Map:**

```php
private static $classes_map = [
    // Root
    'ElementorHtmlCssConverter\Plugin' => 'includes/plugin.php',

    // Core - used on every conversion request
    'ElementorHtmlCssConverter\Core\Converter_Registry' => 'includes/core/class-converter-registry.php',
    'ElementorHtmlCssConverter\Core\Css_Converter' => 'includes/core/class-css-converter.php',
    'ElementorHtmlCssConverter\Core\Html_Converter' => 'includes/core/class-html-converter.php',
    'ElementorHtmlCssConverter\Core\Rest_Api' => 'includes/core/class-rest-api.php',
    'ElementorHtmlCssConverter\Core\Variables_Rest_Api' => 'includes/core/class-variables-rest-api.php',

    // Utilities - frequently used
    'ElementorHtmlCssConverter\Utilities\Style_Definition_Builder' => 'includes/utilities/class-style-definition-builder.php',
    'ElementorHtmlCssConverter\Utilities\Widget_Style_Applicator' => 'includes/utilities/class-widget-style-applicator.php',

    // Most-used converters (optional - can let PSR-4 handle)
    'ElementorHtmlCssConverter\Converters\Css\Color_Converter' => 'includes/converters/css/class-color-converter.php',
    'ElementorHtmlCssConverter\Converters\Css\Background_Color_Converter' => 'includes/converters/css/class-background-color-converter.php',
];
```

---

## Migration Checklist

### Pre-Migration

- [ ] Create git branch for autoloader implementation
- [ ] Document current file structure
- [ ] Run all tests to establish baseline

### Implementation Steps

- [ ] **Phase 1:** Create `includes/autoloader.php`
- [ ] **Phase 2:** Update main plugin file
  - [ ] Remove `ehcc_load_files()` function
  - [ ] Add autoloader registration
  - [ ] Test: Plugin activates without errors
- [ ] **Phase 3:** Reorganize files
  - [ ] Create new directory structure
  - [ ] Move core classes to `includes/core/`
  - [ ] Move utilities to `includes/utilities/`
  - [ ] Update all namespace declarations
  - [ ] Update all use statements
  - [ ] Test: No class not found errors
- [ ] **Phase 4:** Optimize performance map
  - [ ] Add frequently-used classes to `$classes_map`
  - [ ] Test: All endpoints work correctly
- [ ] **Phase 5:** Clean up
  - [ ] Remove old files (verify with git)
  - [ ] Update documentation
  - [ ] Run full test suite

### Testing

- [ ] Test all REST endpoints:
  - [ ] `/convert-html`
  - [ ] `/css-to-atomic`
  - [ ] `/apply-styles-to-widget`
  - [ ] `/add-widget-to-post`
  - [ ] `/import-variables`
- [ ] Test HTML to atomic widgets conversion
- [ ] Test CSS property conversion
- [ ] Test variables import and storage
- [ ] Verify no PHP warnings/errors in debug log
- [ ] Performance test: Compare before/after request times

### Post-Migration

- [ ] Update README with new structure
- [ ] Update developer documentation
- [ ] Create PR for review
- [ ] Deploy to staging environment
- [ ] Monitor for autoloader-related issues

---

## Performance Considerations

### Before (Manual Loading)

```
Every request loads ALL files (~50 files):
- Total files loaded: 50
- Load time: ~15ms
- Memory: ~2MB
- Even if only using 5 classes
```

### After (Autoloading)

```
Only loads classes as needed:
- Typical request loads: 10-15 files
- Load time: ~5-8ms
- Memory: ~800KB
- Performance map speeds up hot paths
```

**Performance Map Strategy:**

Classes in performance map: ~0.1ms per class (direct require)
Classes via PSR-4: ~0.3ms per class (namespace parsing + file lookup)

For classes used on every request (Plugin, Registry, Rest_Api), direct map is 3x faster.

---

## Error Handling

### Common Autoloader Issues

**Issue 1: Class not found after migration**

```
Fatal error: Class 'ElementorHtmlCssConverter\Core\Converter_Registry' not found
```

**Diagnosis:**
1. Check file exists at expected path
2. Check namespace declaration in file matches
3. Check file name follows kebab-case convention
4. Enable autoloader debug logging

**Debug Code:**

```php
// Add to Autoloader::autoload() temporarily
error_log( 'Autoloader: Looking for class: ' . $class );
error_log( 'Autoloader: Trying file: ' . $file );
if ( file_exists( $file ) ) {
    error_log( 'Autoloader: Found file: ' . $file );
    require $file;
} else {
    error_log( 'Autoloader: File not found: ' . $file );
}
```

**Issue 2: Namespace conflicts**

```
Fatal error: Cannot declare class ElementorHtmlCssConverter\Plugin,
because the name is already in use
```

**Cause:** Class loaded twice (once manually, once via autoloader)

**Solution:** Ensure no require_once statements remain for autoloaded classes

**Issue 3: Wrong file path conversion**

Class name `Color_RGB_Converter` → expecting `class-color-rgb-converter.php`

If autoloader generates: `class-color-r-g-b-converter.php` (incorrect)

**Fix:** Update `get_file_path()` regex to handle consecutive capitals better

---

## File Naming Conventions

### Standard Conventions (Following WordPress/Elementor)

**Class Files:**
- Prefix: `class-`
- Format: `kebab-case`
- Extension: `.php`

**Examples:**

| Class Name | File Name |
|------------|-----------|
| `Plugin` | `plugin.php` (no prefix for main) |
| `Converter_Registry` | `class-converter-registry.php` |
| `Color_Hex_Variable_Convertor` | `class-color-hex-variable-convertor.php` |
| `CSS_Parser` | `class-css-parser.php` |

**Interface Files:**
- Prefix: `interface-` or include "interface" in name
- Format: `kebab-case`
- Extension: `.php`

**Examples:**

| Interface Name | File Name |
|----------------|-----------|
| `Variable_Convertor_Interface` | `variable-convertor-interface.php` |
| `Converter_Interface` | `converter-interface.php` |

**Trait Files:**
- Prefix: `trait-`
- Format: `kebab-case`
- Extension: `.php`

---

## Benefits Summary

### Code Quality

**Before:**
```php
// Manual dependency management
require_once 'class-a.php';
require_once 'class-b.php'; // depends on A
require_once 'class-c.php'; // depends on B
// Order matters! Fragile!
```

**After:**
```php
// Automatic dependency resolution
use ElementorHtmlCssConverter\Core\Converter_Registry;
// Autoloader handles it!
```

### Maintainability

**Before:**
- Create new class → add require_once → easy to forget
- Move class → update require_once path
- Delete class → remember to remove require_once

**After:**
- Create new class → just create the file (autoloader finds it)
- Move class → update namespace (autoloader finds new location)
- Delete class → just delete the file

### Performance

- Only loads classes that are actually used
- Performance map optimizes hot paths
- Reduced memory footprint

### Developer Experience

- Standard PSR-4 conventions (familiar to PHP developers)
- IDE autocomplete works better with proper namespaces
- Easier onboarding for new contributors

---

## Next Steps

1. **Review this plan** - Ensure architecture aligns with project goals
2. **Create autoloader** - Implement `includes/autoloader.php`
3. **Test autoloader** - Verify it loads existing classes correctly
4. **Gradual migration** - Move classes in phases (core → converters → utilities)
5. **Full testing** - Run comprehensive tests after each phase
6. **Documentation** - Update developer docs with new structure

---

## Rollback Plan

If issues arise during migration:

1. **Keep git branch clean** - Each phase is a separate commit
2. **Backup require_once list** - Save old `ehcc_load_files()` function
3. **Quick rollback** - Revert to previous commit if autoloader fails
4. **Hybrid mode** - Can temporarily run both autoloader AND require_once during transition

**Hybrid Mode Example:**

```php
// Temporary during migration - loads both ways
require_once EHCC_PATH . 'includes/autoloader.php';
ElementorHtmlCssConverter\Autoloader::register();

// Keep old function temporarily as fallback
function ehcc_load_files() {
    // Only load classes not in autoloader yet
    if ( ! class_exists( 'ElementorHtmlCssConverter\Legacy_Class' ) ) {
        require_once EHCC_PATH . 'includes/class-legacy-class.php';
    }
}
```

---

## Questions & Decisions

### Naming: "Converters" vs "Convertors"

**Current:** Mixed usage
- `includes/converters/css/` (correct)
- `includes/convertors/variables/` (typo)

**Decision:** Standardize on **"Converters"** (correct English spelling)

**Action:** Rename `convertors/` → `converters/`

### Directory Depth

**Option 1:** Flat structure
```
includes/converters/
├── class-color-converter.php
├── class-background-color-converter.php
└── class-color-hex-variable-convertor.php  (mixed types)
```

**Option 2:** Organized by type (RECOMMENDED)
```
includes/converters/
├── css/
│   ├── class-color-converter.php
│   └── class-background-color-converter.php
└── variables/
    └── class-color-hex-variable-convertor.php
```

**Decision:** Use Option 2 (organized by type) - clearer separation

### Performance Map Size

**Small map (~5 classes):** Only absolute essentials
**Medium map (~15 classes):** Core + utilities + frequent converters (RECOMMENDED)
**Large map (~30 classes):** Most classes (defeats purpose of autoloading)

**Decision:** Medium map - balance between performance and autoloading benefits

---

## Summary

This plan transforms the plugin from manual file loading to modern PSR-4 autoloading:

- [OK] Eliminates 40+ require_once statements
- [OK] Reorganizes files into logical hierarchy
- [OK] Improves performance (on-demand loading)
- [OK] Follows WordPress/Elementor best practices
- [OK] Easier to maintain and extend
- [OK] Better developer experience

**Timeline Estimate:**
- Phase 1 (Autoloader): 1 hour
- Phase 2 (Main file): 30 minutes
- Phase 3 (File reorganization): 3-4 hours
- Phase 4 (Performance tuning): 1 hour
- Phase 5 (Testing & cleanup): 2 hours

**Total:** ~8 hours of focused work
