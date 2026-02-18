# Base Styles Override - Testing Guide

## Implementation Summary

The base styles override system has been fully implemented in the elementor-html-css-converter plugin. This allows converted widgets to disable Elementor's default base styles without modifying Elementor core.

### Files Modified

1. **class-atomic-widget-json-creator.php** - Added `editor_settings` flags to converted widgets
2. **includes/plugin.php** - Added enqueue hook and conditional loading logic

### Files Created

1. **assets/js/editor/base-styles-override.js** - JavaScript override implementation

## Testing Checklist

### Test 1: Verify editor_settings Flags

**Goal:** Confirm converted widgets have the required flags.

**Steps:**

1. Convert HTML using the converter REST API:
   ```bash
   curl -X POST "http://your-site.local/wp-json/html-css-converter/v1/convert-html" \
     -H "Content-Type: application/json" \
     -d '{
       "html": "<div style=\"background: #ff0000;\"><h1>Test</h1></div>"
     }'
   ```

2. Open the created post in Elementor editor

3. Open browser console and run:
   ```javascript
   const firstWidget = elementor.getPreviewView().collection.at(0);
   console.log('editor_settings:', firstWidget.get('editor_settings'));
   ```

**Expected output:**
```javascript
{
  css_converter_widget: true,
  disable_base_styles: true
}
```

### Test 2: Base Styles Override Function

**Goal:** Verify `getAtomicWidgetBaseStyles()` returns empty object for converter widgets.

**Steps:**

1. In Elementor editor with converted widgets, open browser console

2. Run:
   ```javascript
   const widget = elementor.getPreviewView().collection.at(0);
   const baseStyles = elementor.helpers.getAtomicWidgetBaseStyles(widget);
   console.log('Base styles:', baseStyles);
   ```

**Expected output:**
```javascript
Base styles: {}
```

**For comparison, test with a non-converter widget:**
```javascript
// Manually add a native e-heading widget
const nativeWidget = elementor.getPreviewView().collection.at(1);
const nativeBaseStyles = elementor.helpers.getAtomicWidgetBaseStyles(nativeWidget);
console.log('Native base styles:', nativeBaseStyles);
```

**Expected:** Should return an object with base style definitions.

### Test 3: Console Log Verification

**Goal:** Verify override script is loading and executing.

**Steps:**

1. Open Elementor editor with converted widgets
2. Open browser console
3. Look for console logs

**Expected logs:**
```
🔥 CSS Converter: Initializing base styles override
🔥 CSS Converter: Document loaded, clearing htmlCache for converter widgets
🔥 CSS Converter: Processing X top-level elements
🔥 CSS Converter: Removing base styles for widget container: e-heading
🔥 CSS Converter: Clearing htmlCache for e-heading widget ID: abc123
```

### Test 4: DOM Inspection

**Goal:** Verify base classes are removed from DOM.

**Steps:**

1. Convert HTML with multiple element types:
   ```html
   <div>
     <h1>Heading</h1>
     <p>Paragraph</p>
     <button>Button</button>
   </div>
   ```

2. Open in Elementor editor

3. Open browser console and run:
   ```javascript
   const iframe = document.querySelector('#elementor-preview-iframe');
   const baseClasses = iframe.contentDocument.querySelectorAll('[class*="-base"]');
   console.log('Base classes found:', baseClasses.length);
   baseClasses.forEach(el => console.log(' -', el.className));
   ```

**Expected output:**
```
Base classes found: 0
```

**Classes that should NOT appear:**
- `e-heading-base`
- `e-paragraph-base`
- `e-button-base`
- `e-div-block-base`

### Test 5: Conditional Loading

**Goal:** Verify script only loads when document has converter widgets.

**Steps:**

1. Create a new blank page (no converter widgets)
2. Open in Elementor editor
3. Check browser console Network tab

**Expected:** `base-styles-override.js` should NOT be loaded

4. Now convert HTML and import to a page
5. Open that page in Elementor editor
6. Check Network tab

**Expected:** `base-styles-override.js` SHOULD be loaded

### Test 6: CSS Precedence

**Goal:** Verify imported CSS takes precedence over Elementor defaults.

**Steps:**

1. Convert HTML with custom styles:
   ```html
   <style>
     .custom-heading { 
       color: #ff0000; 
       margin: 20px;
       font-size: 32px;
     }
   </style>
   <h1 class="custom-heading">Red Heading</h1>
   ```

2. Open in Elementor editor

3. Inspect the h1 element in preview

**Expected:**
- NO `e-heading-base` class applied
- Custom margin (20px) visible (not overridden by base margin: 0)
- Custom color (#ff0000) applied
- Custom font-size (32px) applied

### Test 7: Mixed Content

**Goal:** Verify native and converter widgets coexist properly.

**Steps:**

1. Open a page with converter widgets
2. Manually add a native e-heading widget from panel
3. Check both widgets

**Expected:**
- Converter widget: NO base classes, custom styles active
- Native widget: HAS base classes (e-heading-base), default Elementor styling

### Test 8: Editor Reload

**Goal:** Verify override persists across editor operations.

**Steps:**

1. Open page with converter widgets
2. Verify base classes removed
3. Make a change (e.g., edit text)
4. Save
5. Reload editor

**Expected:** Base classes still removed after reload

### Test 9: Preview Refresh

**Goal:** Verify override works after preview refresh.

**Steps:**

1. Open page with converter widgets
2. Verify base classes removed
3. Click "Preview Changes" or use Ctrl/Cmd+P
4. Check preview window

**Expected:** Base classes still removed in preview

## Common Issues

### Issue: Script Not Loading

**Symptom:** No console logs, base classes still present

**Check:**
```javascript
console.log('Script loaded:', typeof window.cssConverterRenderCompleted);
```

**Solutions:**
- Clear WordPress cache
- Check browser Network tab for 404 errors
- Verify file permissions on `assets/js/editor/base-styles-override.js`

### Issue: editor_settings Not Set

**Symptom:** Override function not detecting converter widgets

**Check in console:**
```javascript
const widget = elementor.getPreviewView().collection.at(0);
console.log('Has flag:', widget.get('editor_settings')?.css_converter_widget);
```

**Solutions:**
- Re-convert HTML (old widgets won't have flags)
- Verify Atomic_Widget_JSON_Creator changes saved
- Clear object cache if using caching plugin

### Issue: Base Classes Persist

**Symptom:** `-base` classes still in DOM after override

**Check:**
```javascript
// Run DOM cleanup manually
const iframe = document.querySelector('#elementor-preview-iframe');
const elements = iframe.contentDocument.querySelectorAll('[class*="-base"]');
console.log('Found base classes:', elements.length);
```

**Solutions:**
- Manually reload preview: `elementor.reloadPreview()`
- Check if htmlCache clearing is working
- Verify DOM cleanup function is executing

## Performance Validation

### Measure Script Impact

```javascript
console.time('base-styles-check');
const widget = elementor.getPreviewView().collection.at(0);
const baseStyles = elementor.helpers.getAtomicWidgetBaseStyles(widget);
console.timeEnd('base-styles-check');
```

**Expected:** < 1ms per widget

### Conditional Loading Impact

Check if script loads only when needed:

1. Create page without converter widgets: Script should NOT load
2. Create page with converter widgets: Script SHOULD load

## Success Criteria

All tests should pass:

- ✅ Converted widgets have `css_converter_widget` and `disable_base_styles` flags
- ✅ Override script loads in Elementor editor (conditionally)
- ✅ `getAtomicWidgetBaseStyles()` returns `{}` for converter widgets
- ✅ Base classes (`e-*-base`) removed from DOM
- ✅ Imported CSS styles take full precedence
- ✅ No Elementor core modifications required
- ✅ Works across editor reload and preview refresh
- ✅ Native widgets unaffected
- ✅ Performance impact minimal (< 1ms per widget check)
