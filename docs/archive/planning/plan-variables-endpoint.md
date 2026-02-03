# CSS Variables Endpoint - Implementation Plan

## Goal
Create a standalone REST API endpoint that imports CSS variable declarations and stores them in Elementor's global variables system.

## Scope
- **ONLY** create the `/import-variables` endpoint
- Accept **raw CSS variable declarations** (no selectors, no wrappers)
- Convert and store in Elementor's variables system
- **NO integration** with existing endpoints (later phase)

---

## Input Format

**Accepted Input:**
```css
--primary-color: #ff0000;
--secondary-color: #00ff00;
--font-size-large: 24px;
--spacing-unit: 1.5rem;
--border-width: 2px;
```

**NOT Accepted (no selectors):**
```css
:root {
  --primary-color: #ff0000;
}
```

---

## Endpoint Specification

### Endpoint Details
- **URL:** `POST /wp-json/html-css-converter/v1/import-variables`
- **Method:** POST
- **Authentication:** WordPress authentication

### Request Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `css` | string | Yes* | - | Raw CSS variable declarations |
| `url` | string | Yes* | - | URL to fetch CSS from |
| `update_mode` | string | No | `'create_new'` | How to handle duplicates: `'create_new'` or `'update'` |

*Either `css` or `url` must be provided

### Request Examples

**Example 1: Direct CSS**
```json
{
  "css": "--primary-color: #ff0000;\n--font-size: 16px;",
  "update_mode": "create_new"
}
```

**Example 2: From URL**
```json
{
  "url": "https://example.com/variables.css",
  "update_mode": "update"
}
```

### Response Format

**Success Response (200):**
```json
{
  "success": true,
  "variables": {
    "primary-color": {
      "name": "--primary-color",
      "value": "#ff0000",
      "type": "color-hex"
    },
    "font-size": {
      "name": "--font-size",
      "value": "16px",
      "type": "size-length-viewport"
    }
  }
}
```

**Error Response (400/422):**
```json
{
  "error": "Missing css or url",
  "code": "invalid_request"
}
```

---

## Architecture

### Component Overview

```
Request
   ↓
Variables_Rest_API
   ↓
Variable_Extractor → extract_from_css()
   ↓
Variable_Conversion_Service → convert_to_editor_variables()
   ↓
Variable_Convertor_Registry → resolve()
   ↓
Specific Convertor (Color_Hex, Length_Size, etc.)
   ↓
Variables_Repository → create() / update()
   ↓
Response
```

---

## Files to Create

### 1. Variable Convertors (Port from elementor-css)

#### Interface
**File:** `includes/convertors/variables/variable-convertor-interface.php`
```php
<?php
namespace ElementorHtmlCssConverter\Convertors\Variables;

interface Variable_Convertor_Interface {
    public function supports( string $name, string $value ): bool;
    public function convert( string $name, string $value ): array;
}
```

#### Abstract Base
**File:** `includes/convertors/variables/convertors/class-abstract-variable-convertor.php`
- Implements `Variable_Convertor_Interface`
- Provides `generate_variable_id()` method
- Child classes implement: `get_type()` and `normalize_value()`

#### Specific Convertors
All in: `includes/convertors/variables/convertors/`

1. **class-color-hex-variable-convertor.php**
   - Supports: `#RGB`, `#RRGGBB`, `#RRGGBBAA`
   - Type: `'color-hex'`

2. **class-color-rgb-variable-convertor.php**
   - Supports: `rgb(r, g, b)`
   - Type: `'color-rgb'`

3. **class-color-rgba-variable-convertor.php**
   - Supports: `rgba(r, g, b, a)`
   - Type: `'color-rgba'`

4. **class-length-size-viewport-variable-convertor.php**
   - Supports: `px`, `pt`, `em`, `rem`, `vh`, `vw`, etc.
   - Type: `'size-length-viewport'`

5. **class-percentage-variable-convertor.php**
   - Supports: `50%`, `100%`, etc.
   - Type: `'size-percentage'`

#### Registry
**File:** `includes/convertors/variables/class-variable-convertor-registry.php`
- Instantiates all 5 convertors
- `resolve()` method finds appropriate convertor by testing `supports()`

---

### 2. Variable Services (New)

#### Conversion Service
**File:** `includes/services/variables/class-variable-conversion-service.php`
```php
class Variable_Conversion_Service {
    public static function convert_to_editor_variables( array $variables ): array {
        $registry = new Variable_Convertor_Registry();
        $converted = [];

        foreach ( $variables as $variable ) {
            $name = $variable['name'] ?? null;
            $value = $variable['value'] ?? null;

            if ( ! is_string( $name ) || ! is_string( $value ) ) {
                continue;
            }

            $convertor = $registry->resolve( $name, $value );

            if ( $convertor ) {
                $converted[] = $convertor->convert( $name, $value );
            }
        }

        return $converted;
    }
}
```

#### Variable Extractor
**File:** `includes/services/variables/class-variable-extractor.php`

**Purpose:** Extract variable declarations from raw CSS

**Method:**
```php
public function extract_from_css( string $css ): array {
    // Pattern: --variable-name: value;
    // Returns: [['name' => '--variable-name', 'value' => 'value'], ...]
}
```

**Logic:**
1. Remove CSS comments
2. Use regex to match: `(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);`
3. Trim whitespace from name and value
4. Return array of name/value pairs

---

### 3. REST API Endpoint

**File:** `includes/class-variables-rest-api.php`

#### Key Methods

**1. `register_route()`**
```php
register_rest_route(
    'html-css-converter/v1',
    '/import-variables',
    [
        'methods' => 'POST',
        'callback' => [ $this, 'import_variables' ],
        'permission_callback' => [ $this, 'check_permissions' ],
        'args' => [
            'css' => ['type' => 'string'],
            'url' => ['type' => 'string'],
            'update_mode' => [
                'type' => 'string',
                'default' => 'create_new',
                'enum' => ['create_new', 'update']
            ]
        ]
    ]
);
```

**2. `import_variables( WP_REST_Request $request )`**

Processing Flow:
```
1. Validate input (css XOR url required)
2. Fetch from URL if needed
3. Remove UTF-8 BOM
4. Extract variables → Variable_Extractor
5. Convert to Elementor format → Variable_Conversion_Service
6. Store in Elementor → store_variables()
7. Return response
```

**3. `store_variables( $repository, $converted, $update_mode )`**

Storage Logic:
```php
if ( 'update' === $update_mode ) {
    // Find existing by label, update if found, create if not
} else { // 'create_new'
    // Check for existing with same value → reuse
    // Otherwise create with incremental suffix
    // Example: primary-color → primary-color-1, primary-color-2
}
```

**Type Mapping:**
```php
'color-hex'             → 'global-color-variable'
'color-rgb'             → 'global-color-variable'
'color-rgba'            → 'global-color-variable'
'size-length-viewport'  → 'global-size-variable'
'size-percentage'       → 'global-size-variable'
```

---

### 4. Plugin Registration

**File:** `includes/class-plugin.php`

Add in initialization:
```php
// Register variables REST API
if ( class_exists( '\ElementorHtmlCssConverter\Variables_Rest_API' ) ) {
    new Variables_Rest_API();
}
```

---

## Implementation Steps

### Step 1: Create Directory Structure
```bash
mkdir -p includes/convertors/variables/convertors
mkdir -p includes/services/variables
```

### Step 2: Port Variable Convertors (6 files)
1. Create interface: `variable-convertor-interface.php`
2. Create abstract base: `class-abstract-variable-convertor.php`
3. Port 5 specific convertors from elementor-css
4. Update namespaces to `ElementorHtmlCssConverter\Convertors\Variables\Convertors`
5. Update class names to match WordPress conventions (underscores)

### Step 3: Create Registry
1. Create `class-variable-convertor-registry.php`
2. Instantiate all 5 convertors
3. Implement `resolve()` method

### Step 4: Create Services
1. Create `class-variable-conversion-service.php` (port from elementor-css)
2. Create `class-variable-extractor.php` (new - simplified)

### Step 5: Create REST Endpoint
1. Create `class-variables-rest-api.php`
2. Implement route registration
3. Implement `import_variables()` handler
4. Implement `store_variables()` method
5. Add error handling and validation
6. Map convertor types to Elementor variable types

### Step 6: Register in Plugin
1. Update `includes/class-plugin.php`
2. Instantiate `Variables_Rest_API` during init

### Step 7: Test
1. Test with color variables
2. Test with size variables
3. Test update_mode: create_new vs update
4. Test duplicate handling
5. Test error cases (invalid input, missing parameters)

---

## Variable Extractor Implementation

**Pattern:** Simple regex (no selector parsing needed)

```php
class Variable_Extractor {

    public function extract_from_css( string $css ): array {
        $variables = [];

        // Remove CSS comments
        $css = preg_replace( '/\/\*.*?\*\//s', '', $css );

        // Pattern: --variable-name: value;
        $pattern = '/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/';

        if ( preg_match_all( $pattern, $css, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $match ) {
                $name = trim( $match[1] );
                $value = trim( $match[2] );

                if ( ! empty( $name ) && ! empty( $value ) ) {
                    $variables[] = [
                        'name' => $name,
                        'value' => $value
                    ];
                }
            }
        }

        return $variables;
    }
}
```

---

## Testing Plan

### Unit Tests

**Test Variable Extraction:**
```php
Input: "--primary-color: #ff0000; --font-size: 16px;"
Expected: [
    ['name' => '--primary-color', 'value' => '#ff0000'],
    ['name' => '--font-size', 'value' => '16px']
]
```

**Test Type Detection:**
```php
'#ff0000' → Color_Hex_Variable_Convertor
'rgb(255, 0, 0)' → Color_Rgb_Variable_Convertor
'16px' → Length_Size_Viewport_Variable_Convertor
'50%' → Percentage_Variable_Convertor
```

**Test Conversion:**
```php
Input: ['name' => '--primary-color', 'value' => '#ff0000']
Expected: [
    'id' => 'e-gv-color-hex-primary-color-variable',
    'type' => 'color-hex',
    'value' => '#ff0000',
    'source' => 'css-variable',
    'name' => '--primary-color'
]
```

### Integration Tests

**Test Full Endpoint:**
1. Send POST request with CSS variables
2. Verify extraction
3. Verify conversion
4. Verify storage in Elementor
5. Check response format

**Test Update Modes:**
1. `create_new`: Same variable twice → creates with suffix
2. `update`: Same variable twice → updates existing

**Test Error Handling:**
1. Missing both `css` and `url` → 400 error
2. Empty CSS → 422 error
3. Invalid CSS → graceful handling

---

## File Structure (Summary)

```
elementor-html-css-converter/
└── includes/
    ├── convertors/
    │   └── variables/
    │       ├── variable-convertor-interface.php
    │       ├── class-variable-convertor-registry.php
    │       └── convertors/
    │           ├── class-abstract-variable-convertor.php
    │           ├── class-color-hex-variable-convertor.php
    │           ├── class-color-rgb-variable-convertor.php
    │           ├── class-color-rgba-variable-convertor.php
    │           ├── class-length-size-viewport-variable-convertor.php
    │           └── class-percentage-variable-convertor.php
    │
    ├── services/
    │   └── variables/
    │       ├── class-variable-conversion-service.php
    │       └── class-variable-extractor.php
    │
    ├── class-variables-rest-api.php
    └── class-plugin.php (update)
```

**Total Files:**
- 6 convertor files (1 interface + 1 base + 4 specific) + 1 registry
- 2 service files
- 1 REST API file
- 1 file to update (plugin.php)

**Total: 11 files** (10 new, 1 update)

---

## Reference Files from elementor-css

For porting reference:

1. **Variable Convertors:**
   - `/elementor-css/modules/css-converter/convertors/variables/variable_convertor_interface.php`
   - `/elementor-css/modules/css-converter/convertors/variables/convertors/abstract_variable_convertor.php`
   - `/elementor-css/modules/css-converter/convertors/variables/convertors/color_hex_variable_convertor.php`
   - `/elementor-css/modules/css-converter/convertors/variables/convertors/color_rgb_variable_convertor.php`
   - `/elementor-css/modules/css-converter/convertors/variables/convertors/color_rgba_variable_convertor.php`
   - `/elementor-css/modules/css-converter/convertors/variables/convertors/length_size_viewport_variable_convertor.php`
   - `/elementor-css/modules/css-converter/convertors/variables/convertors/percentage_variable_convertor.php`
   - `/elementor-css/modules/css-converter/convertors/variables/variable_convertor_registry.php`

2. **Services:**
   - `/elementor-css/modules/css-converter/services/variables/variable-conversion-service.php`

3. **REST Endpoint Pattern:**
   - `/elementor-css/modules/css-converter/routes/variables-route.php` (for storage logic reference)

---

## Success Criteria

✅ Endpoint accepts raw CSS variable declarations
✅ Correctly extracts variables without selector wrappers
✅ Converts color variables (hex, rgb, rgba) to Elementor format
✅ Converts size variables (px, rem, %, etc.) to Elementor format
✅ Stores variables in Elementor's global variables system
✅ Supports both `create_new` and `update` modes
✅ Returns proper response with converted variables
✅ Handles errors gracefully
✅ No integration with other endpoints (isolated functionality)

---

## Next Phase (NOT in this implementation)

After this endpoint is complete and tested:
- Phase 2: Enable var() pass-through in existing CSS converters
- Phase 3: Add variable extraction to HTML converter endpoint

---
