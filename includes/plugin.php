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
use ElementorHtmlCssConverter\Converters\Import\Import_Rest_API;
use ElementorHtmlCssConverter\Editor\Import_Editor;
use ElementorHtmlCssConverter\PostTypes\Import_Template_Post_Type;
use ElementorHtmlCssConverter\Converters\Css\Style_Definition_Builder;
use ElementorHtmlCssConverter\Converters\Css\Widget_Style_Applicator;
use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

		new Variables_Rest_API();

		new Classes_Rest_API( $this->registry );

		new Import_Rest_API();

		new Import_Editor();

		new Import_Template_Post_Type();

		$this->register_editor_hooks();
		$this->register_frontend_hooks();
	}

	private function register_editor_hooks(): void {
		add_action(
			'elementor/editor/before_enqueue_scripts',
			[ $this, 'enqueue_base_styles_override' ],
			10
		);

		add_action(
			'elementor/editor/after_enqueue_styles',
			[ $this, 'enqueue_legacy_atomic_element_override' ],
			10
		);
	}

	private function register_frontend_hooks(): void {
		add_action(
			'elementor/frontend/after_enqueue_styles',
			[ $this, 'enqueue_legacy_atomic_element_override' ],
			10
		);

		add_action(
			'elementor/frontend/before_render',
			[ $this, 'start_base_class_removal_buffer' ],
			PHP_INT_MAX
		);

		add_action(
			'elementor/frontend/after_render',
			[ $this, 'flush_base_class_removal_buffer' ],
			0
		);

		add_filter(
			'elementor/widget/render_content',
			[ $this, 'strip_base_classes_from_widget_content' ],
			10,
			2
		);
	}

	public function enqueue_base_styles_override(): void {
		wp_enqueue_script(
			'ehcc-base-styles-override',
			plugins_url( 'assets/js/editor/base-styles-override.js', EHCC_FILE ),
			[ 'jquery', 'elementor-editor' ],
			EHCC_VERSION,
			true
		);
	}

	public function enqueue_legacy_atomic_element_override(): void {
		wp_enqueue_style(
			'ehcc-legacy-atomic-element-override',
			plugins_url( 'assets/css/legacy-atomic-element-override.css', EHCC_FILE ),
			[],
			EHCC_VERSION
		);
	}

	private array $ob_element_ids = [];

	public function start_base_class_removal_buffer( $element ): void {
		if ( ! $this->is_converter_element( $element ) ) {
			return;
		}

		$element_id = method_exists( $element, 'get_id' ) ? $element->get_id() : null;
		if ( ! $element_id ) {
			return;
		}

		$this->ob_element_ids[] = $element_id;
		ob_start();
	}

	public function flush_base_class_removal_buffer( $element ): void {
		$element_id = method_exists( $element, 'get_id' ) ? $element->get_id() : null;
		if ( ! $element_id ) {
			return;
		}

		if ( empty( $this->ob_element_ids ) || end( $this->ob_element_ids ) !== $element_id ) {
			return;
		}

		array_pop( $this->ob_element_ids );
		$html = ob_get_clean();

		echo $this->strip_base_classes_from_first_tag( $html );
	}

	private function strip_base_classes_from_first_tag( string $html ): string {
		return preg_replace_callback(
			'/^(\s*<\w+\s[^>]*?)class="([^"]*)"/',
			function ( array $matches ): string {
				$classes = explode( ' ', $matches[2] );
				$filtered = array_filter(
					$classes,
					fn( string $class ): bool => ! preg_match( '/^e-[\w-]+-base$/', trim( $class ) )
				);
				return $matches[1] . 'class="' . implode( ' ', $filtered ) . '"';
			},
			$html,
			1
		);
	}

	public function strip_base_classes_from_widget_content( string $content, $widget ): string {
		if ( empty( $content ) ) {
			return $content;
		}

		if ( ! $this->is_converter_element( $widget ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/class="([^"]*)"/',
			function ( array $matches ): string {
				$classes = explode( ' ', $matches[1] );
				$filtered = array_filter(
					$classes,
					fn( string $class ): bool => ! preg_match( '/^e-[\w-]+-base$/', trim( $class ) )
				);
				return 'class="' . implode( ' ', $filtered ) . '"';
			},
			$content
		);
	}

	private function is_converter_element( $element ): bool {
		if ( ! method_exists( $element, 'get_raw_data' ) ) {
			return false;
		}

		$raw_data = $element->get_raw_data();
		$editor_settings = $raw_data['editor_settings'] ?? [];

		if ( is_array( $editor_settings ) ) {
			if ( ! empty( $editor_settings['css_converter_widget'] ) ) {
				return true;
			}

			if ( ! empty( $editor_settings['disable_base_styles'] ) ) {
				return true;
			}
		}

		if ( ! method_exists( $element, 'get_atomic_settings' ) ) {
			return false;
		}

		try {
			$settings = $element->get_atomic_settings();
			$classes = $settings['classes'] ?? [];

			if ( is_array( $classes ) ) {
				foreach ( $classes as $class ) {
					if ( is_string( $class ) && preg_match( '/^e-[a-f0-9]{7,8}-[a-f0-9]{7}$/', $class ) ) {
						return true;
					}
				}
			}
		} catch ( \Throwable $e ) {
			return false;
		}

		if ( $this->has_converter_child( $element ) ) {
			return true;
		}

		return false;
	}

	private function has_converter_child( $element ): bool {
		if ( ! method_exists( $element, 'get_children' ) ) {
			return false;
		}

		foreach ( $element->get_children() as $child ) {
			if ( ! method_exists( $child, 'get_raw_data' ) ) {
				continue;
			}

			$child_raw = $child->get_raw_data();
			$child_editor_settings = $child_raw['editor_settings'] ?? [];

			if ( ! empty( $child_editor_settings['css_converter_widget'] ) || ! empty( $child_editor_settings['disable_base_styles'] ) ) {
				return true;
			}

			if ( ! method_exists( $child, 'get_atomic_settings' ) ) {
				continue;
			}

			try {
				$child_settings = $child->get_atomic_settings();
				$child_classes = $child_settings['classes'] ?? [];

				if ( ! is_array( $child_classes ) ) {
					continue;
				}

				foreach ( $child_classes as $class ) {
					if ( is_string( $class ) && preg_match( '/^e-[a-f0-9]{7,8}-[a-f0-9]{7}$/', $class ) ) {
						return true;
					}
				}
			} catch ( \Throwable $e ) {
				continue;
			}
		}

		return false;
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
