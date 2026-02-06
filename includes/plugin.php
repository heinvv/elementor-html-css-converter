<?php
/**
 * Main Plugin Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use ElementorHtmlCssConverter\Converters\Html\Html_Converter;
use ElementorHtmlCssConverter\Converters\Classes\Rest_Api;
use ElementorHtmlCssConverter\Converters\Classes\Elementor_Document_Service;
use ElementorHtmlCssConverter\Converters\Variables\Variables_Rest_Api;
use ElementorHtmlCssConverter\Converters\Classes\Classes_Rest_API;
use ElementorHtmlCssConverter\Converters\Css\Style_Definition_Builder;
use ElementorHtmlCssConverter\Converters\Css\Widget_Style_Applicator;
use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 *
 * Main plugin orchestration class using singleton pattern.
 */
final class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * The converter registry.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $registry;

	/**
	 * The REST API handler.
	 *
	 * @var Rest_Api
	 */
	private Rest_Api $rest_api;

	/**
	 * The widget style applicator.
	 *
	 * @var Widget_Style_Applicator
	 */
	private Widget_Style_Applicator $widget_style_applicator;

	/**
	 * The HTML converter.
	 *
	 * @var Html_Converter
	 */
	private Html_Converter $html_converter;

	/**
	 * The Elementor document service.
	 *
	 * @var Elementor_Document_Service
	 */
	private Elementor_Document_Service $document_service;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->registry         = new Converter_Registry();
		$this->document_service = new Elementor_Document_Service();

		$this->register_converters();

		$this->widget_style_applicator = $this->create_widget_style_applicator();
		$this->html_converter          = new Html_Converter( $this->registry );

		$this->rest_api = new Rest_Api(
			$this->registry,
			$this->widget_style_applicator,
			$this->html_converter,
			$this->document_service
		);
		$this->rest_api->register_hooks();

		// Initialize Variables REST API
		new Variables_Rest_API();

		// Initialize Classes REST API
		new Classes_Rest_API( $this->registry );
	}

	/**
	 * Create the widget style applicator with its dependencies.
	 *
	 * @return Widget_Style_Applicator The widget style applicator.
	 */
	private function create_widget_style_applicator(): Widget_Style_Applicator {
		$css_converter = new Css_Converter( $this->registry );
		$style_builder = new Style_Definition_Builder();

		return new Widget_Style_Applicator( $css_converter, $style_builder, $this->document_service );
	}

	/**
	 * Register all available converters.
	 *
	 * Automatically discovers and registers all converter classes from the css/properties directory.
	 *
	 * @return void
	 */
	private function register_converters(): void {
		$properties_dir = EHCC_PATH . 'includes/converters/css/properties/';

		if ( ! is_dir( $properties_dir ) ) {
			return;
		}

		$files = glob( $properties_dir . 'class-*-converter.php' );

		foreach ( $files as $file ) {
			$class_name = $this->get_class_name_from_file( $file );

			if ( null === $class_name ) {
				continue;
			}

			$full_class_name = 'ElementorHtmlCssConverter\\Converters\\Css\\Properties\\' . $class_name;

			if ( ! class_exists( $full_class_name ) ) {
				continue;
			}

			$reflection = new \ReflectionClass( $full_class_name );

			if ( $reflection->isAbstract() || $reflection->isInterface() ) {
				continue;
			}

			if ( ! $reflection->implementsInterface( Property_Converter_Interface::class ) ) {
				continue;
			}

			if ( ! $reflection->isInstantiable() ) {
				continue;
			}

			$converter = new $full_class_name();
			$this->registry->register( $converter );
		}
	}

	/**
	 * Extract class name from file path.
	 *
	 * Converts file name like "class-color-converter.php" to "Color_Converter".
	 *
	 * @param string $file_path The file path.
	 * @return string|null The class name or null if invalid.
	 */
	private function get_class_name_from_file( string $file_path ): ?string {
		$filename = basename( $file_path, '.php' );

		if ( strpos( $filename, 'class-' ) !== 0 ) {
			return null;
		}

		$filename = substr( $filename, 6 );

		if ( strpos( $filename, '-converter' ) === false ) {
			return null;
		}

		$filename = str_replace( '-converter', '', $filename );

		$parts = explode( '-', $filename );
		$parts = array_map( 'ucfirst', $parts );

		return implode( '_', $parts );
	}

	/**
	 * Get the converter registry.
	 *
	 * @return Converter_Registry The converter registry.
	 */
	public function get_registry(): Converter_Registry {
		return $this->registry;
	}

	/**
	 * Get the widget style applicator.
	 *
	 * @return Widget_Style_Applicator The widget style applicator.
	 */
	public function get_widget_style_applicator(): Widget_Style_Applicator {
		return $this->widget_style_applicator;
	}

	/**
	 * Get the HTML converter.
	 *
	 * @return Html_Converter The HTML converter.
	 */
	public function get_html_converter(): Html_Converter {
		return $this->html_converter;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @throws \Exception When trying to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
