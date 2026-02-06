# SVG Import Setup Guide

This guide explains how to enable the required permissions for SVG import in the HTML/CSS converter.

## Required Permissions

The SVG import feature requires three things to be enabled:

1. **Enable Unfiltered File Uploads** in Elementor Settings
2. **User permissions** for JSON uploads (via Elementor Role Manager or administrator role)
3. **WordPress SVG mime type** registration

## 1. Enable Unfiltered File Uploads in Elementor

1. Go to **WordPress Admin** → **Elementor** → **Settings** → **Advanced**
2. Find the option **"Enable Unfiltered File Uploads"**
3. Enable it
4. Save changes

**Note:** This setting requires that:
- PHP classes `DOMDocument` and `SimpleXMLElement` are available (usually included in PHP)
- User has permission to upload JSON files (see step 2)

## 2. Enable JSON Upload Permission

You have two options:

### Option A: Use Administrator Role (Easiest)

If your WordPress user is an **Administrator**, you already have the `manage_options` capability, so this requirement is automatically met.

### Option B: Enable via Elementor Role Manager

If you're not an administrator or want to grant this to other roles:

1. Go to **WordPress Admin** → **Elementor** → **Settings** → **Role Manager**
2. Select the user role you want to enable (e.g., Editor, Author)
3. Under **Advanced**, check the option **"Enable the option to upload JSON files"**
4. Save changes

**Security Warning:** Enabling JSON uploads can pose a security risk. Only enable this for trusted users.

## 3. Register SVG Mime Type in WordPress

Add this code to your theme's `functions.php` file or a custom plugin:

```php
add_filter( 'upload_mimes', function( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
} );
```

**Alternative:** If you're using a plugin like **Safe SVG** or **SVG Support**, they usually handle this automatically.

## Verification

After enabling all three requirements, test the SVG import again. The API response should no longer include warnings about permissions, and SVG files should be successfully imported into the WordPress media library.

## Troubleshooting

### Check Current Status

You can check which requirements are met by looking at the `warnings` field in the API response when importing SVG:

```json
{
  "success": true,
  "widgets": [...],
  "warnings": [
    "SVG import requires \"Enable Unfiltered File Uploads\" to be enabled...",
    "SVG import requires user to have manage_options capability...",
    "SVG mime type (image/svg+xml) is not registered..."
  ]
}
```

### Common Issues

1. **"DOMDocument not available"**: This is a PHP configuration issue. Contact your hosting provider to enable the `php-xml` extension.

2. **"Role Manager not working"**: Make sure Elementor is fully activated and the Role Manager module is enabled.

3. **"Mime type still not registered"**: Clear any caching plugins and make sure the `upload_mimes` filter code is active.
