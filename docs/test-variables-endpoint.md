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
- [ ] Test `create_new` mode (creates duplicates with suffix)
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
