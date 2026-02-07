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
		if ( ! self::belongs_to_our_namespace( $class ) ) {
			return;
		}

		if ( self::load_from_performance_map( $class ) ) {
			return;
		}

		if ( self::load_from_psr4_namespace_mapping( $class ) ) {
			return;
		}

		self::load_from_root_namespace_fallback( $class );
	}

	/**
	 * Check if class belongs to our namespace.
	 *
	 * @param string $class Full class name with namespace.
	 * @return bool True if belongs to our namespace, false otherwise.
	 */
	private static function belongs_to_our_namespace( string $class ): bool {
		return strpos( $class, 'ElementorHtmlCssConverter\\' ) === 0;
	}

	/**
	 * Load class from performance map.
	 *
	 * @param string $class Full class name with namespace.
	 * @return bool True if loaded, false otherwise.
	 */
	private static function load_from_performance_map( string $class ): bool {
		if ( ! isset( self::$classes_map[ $class ] ) ) {
			return false;
		}

		$file = EHCC_PATH . self::$classes_map[ $class ];
		if ( file_exists( $file ) ) {
			require $file;
			return true;
		}

		return false;
	}

	/**
	 * Load class from PSR-4 namespace mapping.
	 *
	 * @param string $class Full class name with namespace.
	 * @return bool True if loaded, false otherwise.
	 */
	private static function load_from_psr4_namespace_mapping( string $class ): bool {
		foreach ( self::$namespace_to_path as $namespace => $path ) {
			if ( strpos( $class, $namespace ) === 0 ) {
				$relative_class = self::remove_namespace_prefix( $class, $namespace );
				$file           = self::get_file_path( $relative_class, $path );

				if ( file_exists( $file ) ) {
					require $file;
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Load class from root namespace fallback.
	 *
	 * @param string $class Full class name with namespace.
	 * @return void
	 */
	private static function load_from_root_namespace_fallback( string $class ): void {
		$relative_class = self::remove_namespace_prefix( $class, 'ElementorHtmlCssConverter' );

		if ( self::has_unhandled_subnamespace( $relative_class ) ) {
			return;
		}

		$file = self::get_file_path( $relative_class, 'includes/' );
		if ( file_exists( $file ) ) {
			require $file;
		}
	}

	/**
	 * Remove namespace prefix from class name.
	 *
	 * @param string $class     Full class name with namespace.
	 * @param string $namespace Namespace prefix to remove.
	 * @return string Class name without namespace prefix.
	 */
	private static function remove_namespace_prefix( string $class, string $namespace ): string {
		return str_replace( $namespace . '\\', '', $class );
	}

	/**
	 * Check if class has unhandled subnamespace.
	 *
	 * @param string $relative_class Class name without main namespace.
	 * @return bool True if has unhandled subnamespace, false otherwise.
	 */
	private static function has_unhandled_subnamespace( string $relative_class ): bool {
		return strpos( $relative_class, '\\' ) !== false;
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
		$class_name = self::normalize_namespace_separators( $class_name );
		$parts      = self::split_class_name_into_parts( $class_name );
		$filename   = array_pop( $parts );

		$is_interface = self::is_interface_by_class_name( $filename );
		if ( $is_interface ) {
			$filename = self::remove_interface_suffix_from_filename( $filename );
		}

		$filename = self::convert_class_name_to_kebab_case( $filename );

		$in_interfaces_dir = self::is_in_interfaces_directory( $base_path, $parts );

		$filename = self::add_file_prefix_based_on_type( $filename, $is_interface, $in_interfaces_dir );

		return self::build_full_file_path( $base_path, $parts, $filename );
	}

	/**
	 * Normalize namespace separators to directory separators.
	 *
	 * @param string $class_name Class name with namespace.
	 * @return string Class name with forward slashes.
	 */
	private static function normalize_namespace_separators( string $class_name ): string {
		return str_replace( '\\', '/', $class_name );
	}

	/**
	 * Split class name into directory parts.
	 *
	 * @param string $class_name Class name with forward slashes.
	 * @return array Array of directory parts.
	 */
	private static function split_class_name_into_parts( string $class_name ): array {
		return explode( '/', $class_name );
	}

	/**
	 * Check if class name indicates an interface (ends with _Interface).
	 *
	 * @param string $filename Filename to check.
	 * @return bool True if interface, false otherwise.
	 */
	private static function is_interface_by_class_name( string $filename ): bool {
		return substr( $filename, -10 ) === '_Interface';
	}

	/**
	 * Remove interface suffix from filename.
	 *
	 * @param string $filename Filename with _Interface suffix.
	 * @return string Filename without suffix.
	 */
	private static function remove_interface_suffix_from_filename( string $filename ): string {
		return substr( $filename, 0, -10 );
	}

	/**
	 * Convert class name to kebab-case.
	 *
	 * @param string $filename Class name in PascalCase or Snake_Case.
	 * @return string Filename in kebab-case.
	 */
	private static function convert_class_name_to_kebab_case( string $filename ): string {
		$filename = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $filename ) );
		return str_replace( '_', '-', $filename );
	}

	/**
	 * Check if we're in an interfaces directory.
	 *
	 * @param string $base_path Base directory path.
	 * @param array  $parts     Directory parts.
	 * @return bool True if in interfaces directory, false otherwise.
	 */
	private static function is_in_interfaces_directory( string $base_path, array $parts ): bool {
		return strpos( $base_path, 'interfaces/' ) !== false
			|| in_array( 'Interfaces', $parts, true );
	}

	/**
	 * Add file prefix based on type (interface or class).
	 *
	 * @param string $filename          Filename in kebab-case.
	 * @param bool   $is_interface     Whether this is an interface.
	 * @param bool   $in_interfaces_dir Whether in interfaces directory.
	 * @return string Filename with appropriate prefix and extension.
	 */
	private static function add_file_prefix_based_on_type( string $filename, bool $is_interface, bool $in_interfaces_dir ): string {
		if ( $is_interface || $in_interfaces_dir ) {
			return self::build_interface_filename( $filename );
		}

		if ( self::is_interface_filename( $filename ) ) {
			return $filename . '.php';
		}

		return self::build_class_filename( $filename );
	}

	/**
	 * Build interface filename with prefix.
	 *
	 * @param string $filename Filename in kebab-case.
	 * @return string Interface filename with prefix and extension.
	 */
	private static function build_interface_filename( string $filename ): string {
		if ( self::is_interface_filename( $filename ) ) {
			$filename = self::remove_interface_from_filename( $filename );
		}
		return 'interface-' . $filename . '.php';
	}

	/**
	 * Check if filename contains interface suffix.
	 *
	 * @param string $filename Filename to check.
	 * @return bool True if contains interface suffix, false otherwise.
	 */
	private static function is_interface_filename( string $filename ): bool {
		return strpos( $filename, '-interface' ) !== false;
	}

	/**
	 * Remove interface suffix from filename.
	 *
	 * @param string $filename Filename with interface suffix.
	 * @return string Filename without interface suffix.
	 */
	private static function remove_interface_from_filename( string $filename ): string {
		return str_replace( '-interface', '', $filename );
	}

	/**
	 * Build class filename with prefix.
	 *
	 * @param string $filename Filename in kebab-case.
	 * @return string Class filename with prefix and extension.
	 */
	private static function build_class_filename( string $filename ): string {
		return 'class-' . $filename . '.php';
	}

	/**
	 * Build full file path with directories.
	 *
	 * @param string $base_path Base directory path.
	 * @param array  $parts     Directory parts.
	 * @param string $filename  Filename with prefix and extension.
	 * @return string Full file path.
	 */
	private static function build_full_file_path( string $base_path, array $parts, string $filename ): string {
		if ( empty( $parts ) ) {
			return self::build_path_without_directories( $base_path, $filename );
		}

		return self::build_path_with_directories( $base_path, $parts, $filename );
	}

	/**
	 * Build file path without directory parts.
	 *
	 * @param string $base_path Base directory path.
	 * @param string $filename  Filename with prefix and extension.
	 * @return string Full file path.
	 */
	private static function build_path_without_directories( string $base_path, string $filename ): string {
		return EHCC_PATH . $base_path . $filename;
	}

	/**
	 * Build file path with directory parts.
	 *
	 * @param string $base_path Base directory path.
	 * @param array  $parts     Directory parts.
	 * @param string $filename  Filename with prefix and extension.
	 * @return string Full file path.
	 */
	private static function build_path_with_directories( string $base_path, array $parts, string $filename ): string {
		$directory = self::build_directory_path_from_parts( $parts );
		return EHCC_PATH . $base_path . $directory . '/' . $filename;
	}

	/**
	 * Build directory path from parts.
	 *
	 * @param array $parts Directory parts.
	 * @return string Directory path in lowercase.
	 */
	private static function build_directory_path_from_parts( array $parts ): string {
		return implode( '/', array_map( 'strtolower', $parts ) );
	}
}
