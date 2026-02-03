# Testing the Variables Import Endpoint

## Endpoint
`POST /wp-json/html-css-converter/v1/import-variables`

## Test Examples

### Example 1: Import Color Variables

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": "--primary-color: #ff0000;\n--secondary-color: #00ff00;\n--accent-color: rgb(0, 0, 255);",
    "update_mode": "create_new"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "variables": {
    "primary-color": {
      "name": "--primary-color",
      "value": "#ff0000",
      "type": "color-hex"
    },
    "secondary-color": {
      "name": "--secondary-color",
      "value": "#00ff00",
      "type": "color-hex"
    },
    "accent-color": {
      "name": "--accent-color",
      "value": "rgb(0, 0, 255)",
      "type": "color-rgb"
    }
  }
}
```

### Example 2: Import Size Variables

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": "--font-size-large: 24px;\n--spacing-unit: 1.5rem;\n--container-width: 100%;",
    "update_mode": "create_new"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "variables": {
    "font-size-large": {
      "name": "--font-size-large",
      "value": "24px",
      "type": "size-length-viewport"
    },
    "spacing-unit": {
      "name": "--spacing-unit",
      "value": "1.5rem",
      "type": "size-length-viewport"
    },
    "container-width": {
      "name": "--container-width",
      "value": "100%",
      "type": "size-percentage"
    }
  }
}
```

### Example 3: Mixed Variables

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": "--brand-color: #3498db;\n--heading-size: 2rem;\n--opacity-level: 50%;\n--shadow-color: rgba(0,0,0,0.5);",
    "update_mode": "create_new"
  }'
```

### Example 4: Update Mode

**Request (first time - creates new variable):**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": "--theme-color: #ff0000;",
    "update_mode": "update"
  }'
```

**Request (second time - updates existing variable):**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{
    "css": "--theme-color: #00ff00;",
    "update_mode": "update"
  }'
```

### Example 5: Create New Mode - Value-Aware Deduplication

The `create_new` mode is **value-aware**: it only creates a new variable if the same value doesn't already exist.

**Scenario:**
```bash
# First import - creates "brand-color" with red
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{"css": "--brand-color: #ff0000;", "update_mode": "create_new"}'
# Response: {"created": 1, "reused": 0}

# Second import - different value, creates "brand-color-1" with yellow
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{"css": "--brand-color: #ffff00;", "update_mode": "create_new"}'
# Response: {"created": 1, "reused": 0}

# Third import - different value, creates "brand-color-2" with purple
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{"css": "--brand-color: #800080;", "update_mode": "create_new"}'
# Response: {"created": 1, "reused": 0}

# Fourth import - same value as second (yellow), REUSES "brand-color-1"
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/import-variables" \
  -H "Content-Type: application/json" \
  -d '{"css": "--brand-color: #ffff00;", "update_mode": "create_new"}'
# Response: {"created": 0, "reused": 1} ← No duplicate created!
```

**How it works:**
- Checks if the exact value already exists for this base label (including suffixed versions)
- If value exists → reuses the existing variable (no duplicate created)
- If value is new → creates new variable with incremental suffix
- This prevents creating `brand-color-3`, `brand-color-4`, etc. with duplicate values

## Response Format

All successful responses include:
```json
{
  "success": true,
  "variables": { /* variable data */ },
  "created": 1,   // Number of new variables created
  "reused": 0,    // Number of existing variables reused (create_new mode)
  "updated": 0    // Number of variables updated (update mode)
}
```

## Error Responses

### Missing CSS
```json
{
  "error": "Missing css or url",
  "code": "invalid_request"
}
```

### No Variables Found
```json
{
  "error": "No variables found in CSS",
  "code": "no_variables"
}
```

### No Supported Types
```json
{
  "error": "No supported variable types found",
  "code": "no_supported_types"
}
```

## Supported Variable Types

| Type | Pattern | Example | Convertor Type |
|------|---------|---------|----------------|
| Hex Color | `#RGB`, `#RRGGBB`, `#RRGGBBAA` | `#ff0000` | `color-hex` |
| RGB Color | `rgb(r, g, b)` | `rgb(255, 0, 0)` | `color-rgb` |
| RGBA Color | `rgba(r, g, b, a)` | `rgba(255, 0, 0, 0.5)` | `color-rgba` |
| Length/Size | `px`, `rem`, `em`, `vh`, `vw`, etc. | `16px`, `1.5rem` | `size-length-viewport` |
| Percentage | `%` | `50%`, `100%` | `size-percentage` |

## Testing Checklist

- [ ] Import hex color variables
- [ ] Import RGB color variables
- [ ] Import RGBA color variables
- [ ] Import pixel size variables
- [ ] Import rem/em size variables
- [ ] Import percentage variables
- [ ] Test `create_new` mode (value-aware - only creates duplicate if value is different)
- [ ] Test `update` mode (updates existing)
- [ ] Test error handling (empty CSS)
- [ ] Test error handling (no variables)
- [ ] Verify variables appear in Elementor editor
- [ ] Test with mixed variable types

## WordPress Testing (PHP)

```php
// Test the endpoint programmatically
$request = new WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
$request->set_param( 'css', '--primary-color: #ff0000; --font-size: 16px;' );
$request->set_param( 'update_mode', 'create_new' );

$response = rest_do_request( $request );
$data = $response->get_data();

if ( $data['success'] ) {
    echo "Variables imported successfully!\n";
    print_r( $data['variables'] );
} else {
    echo "Error: " . $data['error'] . "\n";
}
```

## Verification

After importing, verify in Elementor:
1. Go to Elementor > Settings > Global Settings
2. Check the Variables tab
3. Imported variables should appear in the list

---
