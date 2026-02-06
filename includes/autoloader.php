<?php
/**
 * Autoloader
 *
 * PSR-4 compliant class autoloader with performance optimization.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Autoloader
 *
 * Handles automatic class loading using PSR-4 standard with performance optimization.
 */
class Autoloader {

	/**
	 * Performance map for frequently-used classes.
	 *
	 * @var array<string, string>
	 */
	private static $classes_map = [
		// Root
		'ElementorHtmlCssConverter\Plugin' => 'includes/plugin.php',

		// Classes (most frequently instantiated)
		'ElementorHtmlCssConverter\Converters\Classes\Converter_Registry' => 'includes/converters/classes/class-converter-registry.php',
		'ElementorHtmlCssConverter\Converters\Classes\Rest_Api' => 'includes/converters/classes/class-rest-api.php',
		'ElementorHtmlCssConverter\Converters\Classes\Elementor_Document_Service' => 'includes/converters/classes/class-elementor-document-service.php',

		// CSS classes
		'ElementorHtmlCssConverter\Converters\Css\Css_Converter' => 'includes/converters/css/class-css-converter.php',
		'ElementorHtmlCssConverter\Converters\Css\Style_Definition_Builder' => 'includes/converters/css/class-style-definition-builder.php',
		'ElementorHtmlCssConverter\Converters\Css\Widget_Style_Applicator' => 'includes/converters/css/class-widget-style-applicator.php',

		// HTML classes
		'ElementorHtmlCssConverter\Converters\Html\Html_Converter' => 'includes/converters/html/class-html-converter.php',

		// Variables classes
		'ElementorHtmlCssConverter\Converters\Variables\Variables_Rest_Api' => 'includes/converters/variables/class-variables-rest-api.php',
		'ElementorHtmlCssConverter\Converters\Variables\Variable_Extractor' => 'includes/converters/variables/class-variable-extractor.php',
		'ElementorHtmlCssConverter\Converters\Variables\Variable_Conversion_Service' => 'includes/converters/variables/class-variable-conversion-service.php',

		// Most-used converters
		'ElementorHtmlCssConverter\Converters\Css\Properties\Color_Converter' => 'includes/converters/css/properties/class-color-converter.php',
		'ElementorHtmlCssConverter\Converters\Css\Properties\Background_Color_Converter' => 'includes/converters/css/properties/class-background-color-converter.php',
	];

	/**
	 * Namespace to path mapping for PSR-4 autoloading.
	 *
	 * @var array<string, string>
	 */
	private static $namespace_to_path = [
		'ElementorHtmlCssConverter\Converters' => 'includes/converters/',
	];

	/**
	 * Register autoloader.
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
				$file           = self::get_file_path( $relative_class, $path );

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
		$parts    = explode( '/', $class_name );
		$filename = array_pop( $parts );

		// Check if this is an interface by class name (ends with _Interface)
		$is_interface = substr( $filename, -10 ) === '_Interface';

		if ( $is_interface ) {
			$filename = substr( $filename, 0, -10 );
		}

		// Convert class name to kebab-case
		// Color_Converter → color-converter
		// ColorConverter → color-converter
		$filename = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $filename ) );
		$filename = str_replace( '_', '-', $filename );

		// Check if we're in an interfaces directory (either base path or subdirectory)
		$in_interfaces_dir = strpos( $base_path, 'interfaces/' ) !== false
			|| in_array( 'Interfaces', $parts, true );

		// Determine file prefix based on location and type
		if ( $is_interface || $in_interfaces_dir ) {
			// Interface files use 'interface-' prefix
			if ( strpos( $filename, '-interface' ) !== false ) {
				$filename = str_replace( '-interface', '', $filename );
			}
			$filename = 'interface-' . $filename . '.php';
		} elseif ( strpos( $filename, '-interface' ) !== false ) {
			// Other interface files (like variable-convertor-interface) keep name as-is
			$filename = $filename . '.php';
		} else {
			// Regular class files use 'class-' prefix
			$filename = 'class-' . $filename . '.php';
		}

		// Rebuild path with directories
		if ( ! empty( $parts ) ) {
			$directory = implode( '/', array_map( 'strtolower', $parts ) );
			return EHCC_PATH . $base_path . $directory . '/' . $filename;
		}

		return EHCC_PATH . $base_path . $filename;
	}
}
