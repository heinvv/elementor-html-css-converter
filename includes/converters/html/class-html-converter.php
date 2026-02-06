<?php
/**
 * HTML Converter
 *
 * Main orchestrator for HTML to Elementor atomic widget conversion.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

use ElementorHtmlCssConverter\Converters\Html\Atomic_Data_Parser;
use ElementorHtmlCssConverter\Converters\Html\Atomic_Widget_JSON_Creator;
use ElementorHtmlCssConverter\Converters\Html\Widget_Styles_Integrator;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Extractor;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Conversion_Service;
use ElementorHtmlCssConverter\Converters\Variables\Variables_Rest_API;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use ElementorHtmlCssConverter\Converters\Classes\Class_Extractor;
use ElementorHtmlCssConverter\Converters\Classes\Class_Conversion_Service;
use ElementorHtmlCssConverter\Converters\Classes\Class_Registration_Service;
use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use Elementor\Modules\Variables\Storage\Repository as Variables_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Html_Converter
 *
 * Orchestrates the conversion of HTML to Elementor atomic widgets.
 */
class Html_Converter {

	private const REGEX_STYLE_TAG_EXTRACTION = '/<style[^>]*>(.*?)<\/style>/is';
	private const REGEX_CSS_VARIABLE_IN_HTML = '/var\s*\(\s*(--[a-zA-Z0-9_-]+)/';
	private const REGEX_VARIABLE_RENAME_PATTERN = '/var\s*\(\s*%s(\s*,|\s*\))/';

	/**
	 * Data parser instance.
	 *
	 * @var Atomic_Data_Parser
	 */
	private Atomic_Data_Parser $data_parser;

	/**
	 * JSON creator instance.
	 *
	 * @var Atomic_Widget_JSON_Creator
	 */
	private Atomic_Widget_JSON_Creator $json_creator;

	/**
	 * Styles integrator instance.
	 *
	 * @var Widget_Styles_Integrator
	 */
	private Widget_Styles_Integrator $styles_integrator;

	/**
	 * Converter registry instance.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $converter_registry;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry $converter_registry CSS converter registry.
	 */
	public function __construct( Converter_Registry $converter_registry ) {
		$this->converter_registry = $converter_registry;
		$this->data_parser        = new Atomic_Data_Parser( $converter_registry );
		$this->json_creator       = new Atomic_Widget_JSON_Creator();
		$this->styles_integrator  = new Widget_Styles_Integrator();
	}

	/**
	 * Convert HTML to atomic widgets.
	 *
	 * @param string $html    HTML content to convert.
	 * @param array  $options Optional conversion options.
	 * @return array Conversion result.
	 */
	public function convert_html_to_atomic_widgets( string $html, array $options = [] ): array {
		if ( empty( trim( $html ) ) ) {
			return $this->build_error_result( 'HTML content is empty' );
		}

		$status = $this->get_atomic_widgets_status();
		if ( ! $status['available'] ) {
			return $this->build_error_result( $status['reason'] );
		}

		$warnings            = [];
		$imported_variables  = [];
		$imported_classes    = [];
		$global_class_map    = [];
		$variable_renames    = [];
		$css_variables       = $options['css_variables'] ?? '';
		$import_variables    = $options['import_variables'] ?? true;
		$import_classes      = $options['import_classes'] ?? true;
		$update_mode         = $options['update_mode'] ?? 'create_new';

		$css = $this->extract_css_from_html( $html );

		if ( ! empty( trim( $css_variables ) ) ) {
			$import_result      = $this->import_css_variables( $css_variables, $update_mode );
			$imported_variables = array_merge( $imported_variables, $import_result['imported'] ?? [] );
			$variable_renames   = array_merge( $variable_renames, $import_result['renames'] ?? [] );
		}

		if ( $import_variables ) {
			$import_result      = $this->import_css_variables( $css, $update_mode );
			$imported_variables = array_merge( $imported_variables, $import_result['imported'] ?? [] );
			$variable_renames   = array_merge( $variable_renames, $import_result['renames'] ?? [] );
		}

		if ( ! empty( $imported_variables ) ) {
			Variable_Resolver::clear_cache();
		}

		if ( ! empty( $variable_renames ) ) {
			$css = $this->apply_variable_renames( $css, $variable_renames );

			$html = $this->apply_variable_renames_to_html( $html, $variable_renames );
		}

		if ( ! empty( $css_variables ) || $import_variables ) {
			$warnings = $this->check_undefined_variables( $html, $imported_variables );
		}

		$widget_data_array = $this->data_parser->parse_html_for_atomic_widgets( $html );

		if ( empty( $widget_data_array ) ) {
			return $this->build_error_result( 'No supported HTML elements found' );
		}

		if ( $import_classes && ! empty( $css ) ) {
			$class_import_result = $this->import_css_classes( $css, $widget_data_array, $update_mode );
			$imported_classes    = $class_import_result['classes'] ?? [];
			$global_class_map    = $class_import_result['class_map'] ?? [];

			if ( ! empty( $class_import_result['warnings'] ) ) {
				$warnings = array_merge( $warnings, $class_import_result['warnings'] );
			}
		}

		$widgets = $this->create_widgets( $widget_data_array );

		if ( empty( $widgets ) ) {
			return $this->build_error_result( 'No widgets could be created from the HTML' );
		}

		$widgets_with_styles = $this->integrate_styles( $widgets, $widget_data_array, $global_class_map );

		$wrapped_widgets = $this->wrap_non_container_widgets( $widgets_with_styles );

		$result = $this->build_success_result( $wrapped_widgets );

		if ( ! empty( $css_variables ) || $import_variables ) {
			$result['imported_variables'] = $imported_variables;
		}

		if ( $import_classes ) {
			$result['imported_classes'] = $imported_classes;
		}

		if ( ! empty( $warnings ) ) {
			$result['warnings'] = $warnings;
		}

		return $result;
	}

	/**
	 * Create widgets from parsed data.
	 *
	 * @param array $widget_data_array Array of widget data.
	 * @return array Array of widget JSON structures.
	 */
	private function create_widgets( array $widget_data_array ): array {
		$widgets = [];

		foreach ( $widget_data_array as $widget_data ) {
			$widget = $this->json_creator->create_widget_json( $widget_data );
			if ( $widget ) {
				$widgets[] = $widget;
			}
		}

		return $widgets;
	}

	/**
	 * Integrate styles into widgets.
	 *
	 * @param array $widgets           Array of widgets.
	 * @param array $widget_data_array Original widget data with atomic props.
	 * @param array $global_class_map  Map of CSS class names to Elementor global class IDs.
	 * @return array Widgets with integrated styles.
	 */
	private function integrate_styles( array $widgets, array $widget_data_array, array $global_class_map = [] ): array {
		$widgets_with_styles = [];

		foreach ( $widgets as $index => $widget ) {
			$widget_data = $widget_data_array[ $index ] ?? [];
			$widget      = $this->integrate_styles_recursive( $widget, $widget_data, $global_class_map );

			$widgets_with_styles[] = $widget;
		}

		return $widgets_with_styles;
	}

	/**
	 * Recursively integrate styles into a widget and its children.
	 *
	 * @param array $widget           Widget JSON structure.
	 * @param array $widget_data      Original widget data with atomic props and children.
	 * @param array $global_class_map Map of CSS class names to Elementor global class IDs.
	 * @return array Widget with integrated styles.
	 */
	private function integrate_styles_recursive( array $widget, array $widget_data, array $global_class_map = [] ): array {
		$atomic_props    = $widget_data['atomic_props'] ?? [];
		$element_classes = $widget_data['element_classes'] ?? [];

		$global_class_ids = [];
		foreach ( $element_classes as $class_name ) {
			if ( isset( $global_class_map[ $class_name ] ) ) {
				$global_class_ids[] = $global_class_map[ $class_name ];
			}
		}

		if ( ! isset( $widget['settings']['classes'] ) ) {
			$widget['settings']['classes'] = [
				'$$type' => 'classes',
				'value'  => [],
			];
		}

		if ( ! empty( $global_class_ids ) ) {
			$existing_classes                    = $widget['settings']['classes']['value'] ?? [];
			$widget['settings']['classes']['value'] = array_merge( $existing_classes, $global_class_ids );
		}

		if ( ! empty( $atomic_props ) ) {
			$widget = $this->styles_integrator->integrate_styles_into_widget( $widget, $atomic_props );
		}

		if ( ! empty( $widget['elements'] ) && ! empty( $widget_data['children'] ) ) {
			$processed_children = [];

			foreach ( $widget['elements'] as $child_index => $child_widget ) {
				$child_data           = $widget_data['children'][ $child_index ] ?? [];
				$processed_children[] = $this->integrate_styles_recursive( $child_widget, $child_data, $global_class_map );
			}

			$widget['elements'] = $processed_children;
		}

		return $widget;
	}

	/**
	 * Wrap non-container top-level widgets in div containers.
	 *
	 * Elementor requires top-level elements to be containers.
	 * Non-container widgets (heading, paragraph, button, image) must be wrapped.
	 *
	 * @param array $widgets Array of widgets.
	 * @return array Widgets with non-containers wrapped.
	 */
	private function wrap_non_container_widgets( array $widgets ): array {
		$wrapped_widgets = [];

		foreach ( $widgets as $widget ) {
			if ( $this->is_container_widget( $widget ) ) {
				$wrapped_widgets[] = $widget;
			} else {
				$wrapped_widgets[] = $this->create_wrapper_container( $widget );
			}
		}

		return $wrapped_widgets;
	}

	/**
	 * Check if a widget is a container type.
	 *
	 * @param array $widget Widget data.
	 * @return bool True if container.
	 */
	private function is_container_widget( array $widget ): bool {
		$container_types = [ 'e-div-block', 'e-flexbox', 'container' ];

		$el_type = $widget['elType'] ?? '';
		if ( in_array( $el_type, $container_types, true ) ) {
			return true;
		}

		$widget_type = $widget['widgetType'] ?? '';
		if ( in_array( $widget_type, $container_types, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Create a wrapper div-block container for a widget.
	 *
	 * @param array $widget Widget to wrap.
	 * @return array Wrapper container with widget as child.
	 */
	private function create_wrapper_container( array $widget ): array {
		$wrapper = [
			'elType'          => 'e-div-block',
			'settings'        => [
				'classes' => [
					'$$type' => 'classes',
					'value'  => [],
				],
			],
			'isLocked'        => false,
			'editor_settings' => [],
			'elements'        => [ $widget ],
			'styles'          => [],
		];

		return $wrapper;
	}

	/**
	 * Build error result.
	 *
	 * @param string $message Error message.
	 * @return array Error result.
	 */
	private function build_error_result( string $message ): array {
		return [
			'success' => false,
			'error'   => $message,
			'widgets' => [],
		];
	}

	/**
	 * Build success result.
	 *
	 * @param array $widgets Array of widgets.
	 * @return array Success result.
	 */
	private function build_success_result( array $widgets ): array {
		return [
			'success' => true,
			'widgets' => $widgets,
		];
	}

	/**
	 * Extract CSS from all <style> tags in HTML.
	 *
	 * @param string $html HTML content.
	 * @return string Combined CSS from all style tags.
	 */
	private function extract_css_from_html( string $html ): string {
		preg_match_all( self::REGEX_STYLE_TAG_EXTRACTION, $html, $matches );
		return implode( "\n", $matches[1] ?? [] );
	}

	/**
	 * Apply variable renames to CSS.
	 *
	 * When variables are created with suffixes (e.g., --color-1 instead of --color),
	 * this method updates all var() references in CSS to use the new names.
	 *
	 * @param string $css     CSS content.
	 * @param array  $renames Map of original names to final names (e.g., '--color' => '--color-1').
	 * @return string CSS with updated variable references.
	 */
	private function apply_variable_renames( string $css, array $renames ): string {
		foreach ( $renames as $original => $renamed ) {
			$pattern     = sprintf( self::REGEX_VARIABLE_RENAME_PATTERN, preg_quote( $original, '/' ) );
			$replacement = 'var(' . $renamed . '$1';
			$css         = preg_replace( $pattern, $replacement, $css );
		}

		return $css;
	}

	/**
	 * Apply variable renames to HTML by updating <style> tag contents.
	 *
	 * Updates var() references inside <style> tags so the parser sees
	 * the correct (renamed) variable names.
	 *
	 * @param string $html    HTML content.
	 * @param array  $renames Map of original names to final names.
	 * @return string HTML with updated variable references in <style> tags.
	 */
	private function apply_variable_renames_to_html( string $html, array $renames ): string {
		return preg_replace_callback(
			'/<style([^>]*)>(.*?)<\/style>/is',
			function ( $matches ) use ( $renames ) {
				$attributes = $matches[1];
				$css        = $matches[2];

				$css = $this->apply_variable_renames( $css, $renames );

				return '<style' . $attributes . '>' . $css . '</style>';
			},
			$html
		);
	}

	/**
	 * Import CSS variables from extracted CSS.
	 *
	 * @param string $css         CSS content containing variable declarations.
	 * @param string $update_mode Update mode: 'create_new' or 'update'.
	 * @return array Import result with list of imported variable names and renames mapping.
	 */
	private function import_css_variables( string $css, string $update_mode ): array {
		if ( empty( trim( $css ) ) ) {
			return [ 'imported' => [], 'renames' => [] ];
		}

		$extractor = new Variable_Extractor();
		$raw_vars  = $extractor->extract_from_css( $css );

		if ( empty( $raw_vars ) ) {
			return [ 'imported' => [], 'renames' => [] ];
		}

		$converted = Variable_Conversion_Service::convert_to_editor_variables( $raw_vars );

		if ( empty( $converted ) ) {
			return [ 'imported' => [], 'renames' => [] ];
		}

		$api = new Variables_Rest_API();

		$store_result = $api->store_variables( $converted, $update_mode );

		$imported_names = array_column( $converted, 'name' );

		return [
			'imported'     => $imported_names,
			'renames'      => $store_result['renames'] ?? [],
			'store_result' => $store_result,
		];
	}

	/**
	 * Import CSS classes from extracted CSS.
	 *
	 * Only imports classes that are actually used in the HTML elements.
	 *
	 * @param string $css               CSS content containing class selectors.
	 * @param array  $widget_data_array Parsed widget data with element_classes.
	 * @param string $update_mode       Update mode: 'create_new' or 'update'.
	 * @return array Import result with classes info and class_map.
	 */
	private function import_css_classes( string $css, array $widget_data_array, string $update_mode ): array {
		$result = [
			'classes'   => [],
			'class_map' => [],
			'warnings'  => [],
			'skipped'   => [],
		];

		if ( empty( trim( $css ) ) ) {
			return $result;
		}

		$used_classes = $this->collect_used_classes( $widget_data_array );

		if ( empty( $used_classes ) ) {
			return $result;
		}

		$extractor         = new Class_Extractor();
		$extracted_classes = $extractor->extract_from_css( $css );

		if ( empty( $extracted_classes ) ) {
			return $result;
		}

		$classes_to_import = [];
		foreach ( $extracted_classes as $class_name => $class_data ) {
			if ( in_array( $class_name, $used_classes, true ) ) {
				$classes_to_import[ $class_name ] = $class_data;
			} else {
				$result['skipped'][] = [
					'selector' => '.' . $class_name,
					'reason'   => 'not used in HTML',
				];
			}
		}

		if ( empty( $classes_to_import ) ) {
			return $result;
		}

		$conversion_service = new Class_Conversion_Service( $this->converter_registry );
		$converted_classes  = $conversion_service->convert_to_atomic( $classes_to_import );

		if ( empty( $converted_classes ) ) {
			$result['warnings'][] = 'No classes could be converted to atomic format';
			return $result;
		}

		$registration_service = new Class_Registration_Service();
		$registration_result  = $registration_service->register_with_elementor(
			$converted_classes,
			$update_mode,
			'frontend'
		);

		if ( ! $registration_result['success'] ) {
			$result['warnings'][] = $registration_result['error'] ?? 'Failed to register global classes';
			return $result;
		}

		foreach ( $registration_result['classes'] as $class_name => $class_info ) {
			$result['class_map'][ $class_name ] = $class_info['elementor_id'];
			$result['classes'][ $class_name ]   = [
				'label'        => $class_info['label'],
				'elementor_id' => $class_info['elementor_id'],
				'status'       => $class_info['status'],
			];
		}

		foreach ( $registration_result['overflow'] as $overflow_class ) {
			$result['skipped'][] = [
				'selector' => '.' . $overflow_class,
				'reason'   => 'global class limit reached (100 max)',
			];
		}

		return $result;
	}

	/**
	 * Collect all class names used in HTML elements from widget data.
	 *
	 * @param array $widget_data_array Parsed widget data.
	 * @return array List of unique class names.
	 */
	private function collect_used_classes( array $widget_data_array ): array {
		$classes = [];

		foreach ( $widget_data_array as $widget_data ) {
			$element_classes = $widget_data['element_classes'] ?? [];
			$classes         = array_merge( $classes, $element_classes );

			if ( ! empty( $widget_data['children'] ) ) {
				$child_classes = $this->collect_used_classes( $widget_data['children'] );
				$classes       = array_merge( $classes, $child_classes );
			}
		}

		return array_unique( $classes );
	}

	/**
	 * Check for undefined var() references in HTML.
	 *
	 * @param string $html               HTML content.
	 * @param array  $imported_variables List of imported variable names.
	 * @return array List of warning messages.
	 */
	private function check_undefined_variables( string $html, array $imported_variables ): array {
		$warnings = [];

		preg_match_all( self::REGEX_CSS_VARIABLE_IN_HTML, $html, $matches );

		if ( empty( $matches[1] ) ) {
			return $warnings;
		}

		$active_kit = \Elementor\Plugin::$instance->kits_manager->get_active_id();
		if ( ! $active_kit ) {
			return $warnings;
		}

		$repository              = new Variables_Repository( \Elementor\Plugin::$instance->kits_manager->get_active_kit() );
		$existing_variable_names = [];

		try {
			$db_record = $repository->load();
			$existing  = isset( $db_record['data'] ) && is_array( $db_record['data'] ) ? $db_record['data'] : [];

			foreach ( $existing as $item ) {
				if ( isset( $item['deleted'] ) && $item['deleted'] ) {
					continue;
				}

				if ( isset( $item['label'] ) ) {
					$existing_variable_names[] = '--' . $item['label'];
				}
			}
		} catch ( \Exception $e ) {
		}

		$all_defined_variables = array_merge( $imported_variables, $existing_variable_names );

		foreach ( array_unique( $matches[1] ) as $var_name ) {
			if ( ! in_array( $var_name, $all_defined_variables, true ) ) {
				$warnings[] = "Variable '{$var_name}' used but not defined";
			}
		}

		return $warnings;
	}

	/**
	 * Check if atomic widgets module is available.
	 *
	 * @return bool True if available.
	 */
	public function is_atomic_widgets_available(): bool {
		return class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Widget_Builder' ) &&
				class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Element_Builder' );
	}

	/**
	 * Get detailed status of atomic widgets availability.
	 *
	 * @return array Status info with reason if not available.
	 */
	public function get_atomic_widgets_status(): array {
		if ( ! class_exists( 'Elementor\\Plugin' ) ) {
			return [
				'available' => false,
				'reason'    => 'Elementor plugin is not active',
			];
		}

		if ( ! isset( \Elementor\Plugin::$instance ) || ! \Elementor\Plugin::$instance ) {
			return [
				'available' => false,
				'reason'    => 'Elementor not fully initialized',
			];
		}

		if ( ! isset( \Elementor\Plugin::$instance->experiments ) ) {
			return [
				'available' => false,
				'reason'    => 'Elementor experiments system not available',
			];
		}

		$experiment_active = \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_atomic_elements' );
		if ( ! $experiment_active ) {
			return [
				'available' => false,
				'reason'    => 'Atomic Elements experiment is not enabled. Enable it in Elementor > Settings > Features > Atomic Elements',
			];
		}

		if ( ! class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Widget_Builder' ) ) {
			return [
				'available' => false,
				'reason'    => 'Widget_Builder class not found',
			];
		}

		if ( ! class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Element_Builder' ) ) {
			return [
				'available' => false,
				'reason'    => 'Element_Builder class not found',
			];
		}

		return [
			'available' => true,
			'reason'    => null,
		];
	}

	/**
	 * Get supported HTML tags.
	 *
	 * @return array List of supported tags.
	 */
	public function get_supported_html_tags(): array {
		return $this->data_parser->get_widget_mapper()->get_supported_tags();
	}

	/**
	 * Get supported widget types.
	 *
	 * @return array List of supported widget types.
	 */
	public function get_supported_widget_types(): array {
		return $this->json_creator->get_supported_widget_types();
	}

	/**
	 * Get conversion capabilities.
	 *
	 * @return array Conversion capabilities info.
	 */
	public function get_conversion_capabilities(): array {
		$status = $this->get_atomic_widgets_status();

		return [
			'atomic_widgets_available' => $status['available'],
			'atomic_widgets_reason'    => $status['reason'],
			'supported_html_tags'      => $this->get_supported_html_tags(),
			'supported_widget_types'   => $this->get_supported_widget_types(),
		];
	}
}

