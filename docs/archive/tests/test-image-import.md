# Image Import Test Examples

## Overview

This document provides comprehensive test scenarios for the image import functionality in the HTML/CSS converter. The `import_images` option allows external images from `<img>` tags and `background-image` CSS properties to be automatically downloaded and imported into the WordPress media library.

---

## Test 1: External Image in `<img>` Tag (Import Enabled)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<img src=\"https://example.com/image.jpg\" alt=\"Test Image\" width=\"300\" height=\"200\">",
    "import_images": true
  }'
```

**Alternative (using options object):**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<img src=\"https://example.com/image.jpg\" alt=\"Test Image\" width=\"300\" height=\"200\">",
    "options": {
      "import_images": true
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "elements": [
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": 123,
                  "url": null
                }
              },
              "size": "full"
            },
            "alt": "Test Image",
            "width": "300",
            "height": "200"
          }
        }
      ]
    }
  ],
  "imported_images": []
}
```

**Verification:**
- Image URL is replaced with WordPress attachment ID
- `value.id` contains the imported attachment ID
- `value.url` is `null` (indicating local attachment)
- Image appears in WordPress Media Library

---

## Test 2: External Background Image in CSS (Import Enabled)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>#hero { background-image: url(https://example.com/background.jpg); }</style><div id=\"hero\">Hero Section</div>",
    "options": {
      "import_images": true
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "settings": {
        "classes": {
          "$$type": "classes",
          "value": []
        }
      },
      "styles": {
        "desktop": {
          "background": {
            "$$type": "background",
            "value": {
              "background-overlay": {
                "$$type": "background-overlay",
                "value": {
                  "image": {
                    "$$type": "image",
                    "value": {
                      "src": {
                        "$$type": "image-src",
                        "value": {
                          "id": 124,
                          "url": null
                        }
                      },
                      "size": "full"
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  ],
  "imported_images": []
}
```

**Verification:**
- Background image URL is replaced with WordPress attachment ID
- Image ID is nested correctly in background prop structure
- Background image appears in WordPress Media Library

---

## Test 3: Mixed Local and External Images (Import Enabled)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<img src=\"https://example.com/external.jpg\" alt=\"External\"><img src=\"/wp-content/uploads/2024/01/local.jpg\" alt=\"Local\">",
    "options": {
      "import_images": true
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "elements": [
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": 125,
                  "url": null
                }
              }
            }
          }
        },
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": 126,
                  "url": null
                }
              }
            }
          }
        }
      ]
    }
  ]
}
```

**Verification:**
- External image is imported (new attachment ID)
- Local image uses existing attachment ID (no duplicate import)
- Both images have attachment IDs set

---

## Test 4: Image Import Disabled (External URL Preserved)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<img src=\"https://example.com/image.jpg\" alt=\"Test\">",
    "options": {
      "import_images": false
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "elements": [
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": null,
                  "url": "https://example.com/image.jpg"
                }
              }
            }
          }
        }
      ]
    }
  ]
}
```

**Verification:**
- External URL is preserved
- `value.id` is `null`
- `value.url` contains the original external URL
- No import occurs

---

## Test 5: Multiple External Images (Import Enabled)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>#box1 { background-image: url(https://example.com/bg1.jpg); } #box2 { background-image: url(https://example.com/bg2.jpg); }</style><div id=\"box1\">Box 1</div><div id=\"box2\">Box 2</div><img src=\"https://example.com/photo.jpg\" alt=\"Photo\">",
    "options": {
      "import_images": true
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "settings": {},
      "styles": {
        "desktop": {
          "background": {
            "$$type": "background",
            "value": {
              "background-overlay": {
                "$$type": "background-overlay",
                "value": {
                  "image": {
                    "$$type": "image",
                    "value": {
                      "src": {
                        "$$type": "image-src",
                        "value": {
                          "id": 127,
                          "url": null
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    {
      "elType": "e-div-block",
      "settings": {},
      "styles": {
        "desktop": {
          "background": {
            "$$type": "background",
            "value": {
              "background-overlay": {
                "$$type": "background-overlay",
                "value": {
                  "image": {
                    "$$type": "image",
                    "value": {
                      "src": {
                        "$$type": "image-src",
                        "value": {
                          "id": 128,
                          "url": null
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    {
      "elType": "e-div-block",
      "elements": [
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": 129,
                  "url": null
                }
              }
            }
          }
        }
      ]
    }
  ]
}
```

**Verification:**
- All three external images are imported
- Each image gets a unique attachment ID
- Background images and `<img>` tags are both handled
- No duplicate imports for the same URL

---

## Test 6: Invalid Image URL (Import Enabled, Graceful Fallback)

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<img src=\"https://invalid-domain-that-does-not-exist-12345.com/image.jpg\" alt=\"Invalid\">",
    "options": {
      "import_images": true
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "elements": [
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": null,
                  "url": "https://invalid-domain-that-does-not-exist-12345.com/image.jpg"
                }
              }
            }
          }
        }
      ]
    }
  ]
}
```

**Verification:**
- Conversion succeeds (does not fail on import error)
- Original URL is preserved when import fails
- No attachment ID is set
- Error is handled gracefully

---

## Test 7: Combined with Other Options

**Endpoint:** `POST /wp-json/html-css-converter/v1/convert-html`

**Request:**
```bash
curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<style>.card { background-image: url(https://example.com/card-bg.jpg); }</style><div class=\"card\"><img src=\"https://example.com/avatar.jpg\" alt=\"Avatar\"></div>",
    "options": {
      "import_images": true,
      "import_classes": true,
      "import_variables": false,
      "update_mode": "create_new"
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "widgets": [
    {
      "elType": "e-div-block",
      "settings": {
        "classes": {
          "$$type": "classes",
          "value": [123]
        }
      },
      "styles": {
        "desktop": {
          "background": {
            "$$type": "background",
            "value": {
              "background-overlay": {
                "$$type": "background-overlay",
                "value": {
                  "image": {
                    "$$type": "image",
                    "value": {
                      "src": {
                        "$$type": "image-src",
                        "value": {
                          "id": 130,
                          "url": null
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      },
      "elements": [
        {
          "elType": "widget",
          "widgetType": "e-image",
          "settings": {
            "image": {
              "src": {
                "$$type": "image-src",
                "value": {
                  "id": 131,
                  "url": null
                }
              }
            }
          }
        }
      ]
    }
  ],
  "imported_classes": {
    "card": {
      "label": "card",
      "elementor_id": 123,
      "status": "created"
    }
  }
}
```

**Verification:**
- Image import works alongside class import
- Both background image and `<img>` tag images are imported
- Global class is created and linked to widget
- All options work together correctly

---

## PHP Code Example

```php
<?php

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Html\Html_Converter;

$registry = new Converter_Registry();
$converter = new Html_Converter( $registry );

$html = '
<style>
  #hero {
    background-image: url(https://example.com/hero-bg.jpg);
  }
</style>
<div id="hero">
  <h1>Welcome</h1>
  <img src="https://example.com/logo.png" alt="Logo">
</div>
';

$result = $converter->convert_html_to_atomic_widgets( $html, [
    'import_images' => true,
    'import_classes' => true,
] );

if ( $result['success'] ) {
    echo "Conversion successful!\n";
    echo "Widgets created: " . count( $result['widgets'] ) . "\n";
    
    if ( isset( $result['imported_images'] ) ) {
        echo "Images imported: " . count( $result['imported_images'] ) . "\n";
    }
} else {
    echo "Error: " . $result['error'] . "\n";
}
```

---

## Notes

- **Performance**: Image import happens synchronously and may slow down conversion for many images
- **Duplicate Detection**: Elementor's import system uses hash-based duplicate detection - the same image URL imported twice will reuse the existing attachment
- **Local URLs**: URLs that are already local (from the same WordPress site) are detected and use existing attachment IDs without re-importing
- **Error Handling**: If import fails (network error, invalid URL, etc.), the conversion continues with the original external URL
- **Default Behavior**: `import_images` defaults to `false` - external URLs are displayed directly without import
