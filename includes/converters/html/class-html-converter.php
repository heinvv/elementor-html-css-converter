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
use ElementorHtmlCssConverter\Converters\Variables\Variable_Fallback_Substitutor;
use ElementorHtmlCssConverter\Converters\Variables\Variables_Rest_API;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use ElementorHtmlCssConverter\Converters\Classes\Class_Extractor;
use ElementorHtmlCssConverter\Converters\Classes\Class_Conversion_Service;
use ElementorHtmlCssConverter\Converters\Classes\Class_Registration_Service;
use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use ElementorHtmlCssConverter\Converters\Images\Image_Import_Service;
use ElementorHtmlCssConverter\Converters\Import\Import_Timing_Collector;
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
	 * Breakpoint matcher instance.
	 *
	 * @var Breakpoint_Matcher
	 */
	private Breakpoint_Matcher $breakpoint_matcher;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry      $converter_registry CSS converter registry.
	 * @param Breakpoint_Matcher|null $breakpoint_matcher Optional breakpoint matcher instance.
	 */
	public function __construct( Converter_Registry $converter_registry, ?Breakpoint_Matcher $breakpoint_matcher = null ) {
		$this->converter_registry  = $converter_registry;
		$this->breakpoint_matcher  = $breakpoint_matcher ?? new Breakpoint_Matcher();
		$this->data_parser         = new Atomic_Data_Parser( $converter_registry, $this->breakpoint_matcher );
		$this->json_creator        = new Atomic_Widget_JSON_Creator( false );
		$this->styles_integrator   = new Widget_Styles_Integrator();
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
		$imported_images     = [];
		$global_class_map    = [];
		$variable_renames    = [];
		$css_variables       = $options['css_variables'] ?? '';
		$import_variables    = $options['import_variables'] ?? true;
		$import_classes      = $options['import_classes'] ?? true;
		$import_images       = $options['import_images'] ?? true;
		$update_mode         = $options['update_mode'] ?? 'create_new';
		$timing              = $options['timing_collector'] ?? null;

		$t0 = $timing ? microtime( true ) : 0;
		$css = $this->extract_css_from_html( $html );
		if ( $timing ) {
			$timing->record( 'extract_css_ms', $t0 );
		}

		$t0 = $timing ? microtime( true ) : 0;
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
		if ( $timing ) {
			$timing->record( 'import_variables_ms', $t0 );
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

		$variable_fallback = $this->build_variable_fallback_map( $css_variables, $css, $variable_renames );
		$t0 = $timing ? microtime( true ) : 0;
		$widget_data_array = $this->data_parser->parse_html_for_atomic_widgets( $html, [
			'variable_fallback' => $variable_fallback,
		] );
		$raw_body_styles = $this->data_parser->get_body_styles();
		if ( $timing ) {
			$timing->record( 'parse_html_ms', $t0 );
		}

		if ( empty( $widget_data_array ) ) {
			return $this->build_error_result( 'No supported HTML elements found' );
		}

		if ( $import_classes && ! empty( $css ) ) {
			$t0 = $timing ? microtime( true ) : 0;
			$class_import_result = $this->import_css_classes( $css, $widget_data_array, $update_mode );
			$imported_classes    = $class_import_result['classes'] ?? [];
			$global_class_map    = $class_import_result['class_map'] ?? [];

			if ( ! empty( $class_import_result['warnings'] ) ) {
				$warnings = array_merge( $warnings, $class_import_result['warnings'] );
			}
			if ( $timing ) {
				$timing->record( 'import_classes_ms', $t0 );
			}
		}

		$json_creator = new Atomic_Widget_JSON_Creator( $import_images );
		$t0 = $timing ? microtime( true ) : 0;
		$widgets = $json_creator->create_multiple_widgets( $widget_data_array );
		if ( $timing ) {
			$timing->record( 'create_widgets_ms', $t0 );
		}

		if ( empty( $widgets ) ) {
			return $this->build_error_result( 'No widgets could be created from the HTML' );
		}

		$svg_warnings = $json_creator->get_warnings();
		if ( ! empty( $svg_warnings ) ) {
			$warnings = array_merge( $warnings, $svg_warnings );
		}

		$t0 = $timing ? microtime( true ) : 0;
		$widgets_with_styles = $this->integrate_styles( $widgets, $widget_data_array, $global_class_map );
		if ( $timing ) {
			$timing->record( 'integrate_styles_ms', $t0 );
		}

		$import_warnings = [];
		if ( $import_images ) {
			$t0 = $timing ? microtime( true ) : 0;
			$image_import_service = new Image_Import_Service();
			$import_result = $image_import_service->import_images_in_widgets( $widgets_with_styles );
			$widgets_with_styles = $import_result['widgets'];
			$imported_images = $import_result['imported'] ?? [];
			$import_warnings = $import_result['warnings'] ?? [];
			if ( $timing ) {
				$timing->record( 'import_images_ms', $t0 );
			}
		} else {
			$imported_images = [];
		}

		$t0 = $timing ? microtime( true ) : 0;
		$wrapped_widgets = $this->wrap_non_container_widgets( $widgets_with_styles );

		$wrapped_widgets = $this->assign_element_ids_recursive( $wrapped_widgets );
		if ( $timing ) {
			$timing->record( 'wrap_assign_ids_ms', $t0 );
		}

		$result = $this->build_success_result( $wrapped_widgets );

		$body_page_settings = $this->build_body_page_settings(
			$raw_body_styles['breakpoint_rules']
		);
		if ( null !== $body_page_settings ) {
			$result['body_page_settings'] = $body_page_settings;
		}

		if ( ! empty( $css_variables ) || $import_variables ) {
			$result['imported_variables'] = $imported_variables;
		}

		if ( $import_classes ) {
			$result['imported_classes'] = $imported_classes;
		}

		if ( $import_images ) {
			$result['imported_images'] = $imported_images;
			if ( ! empty( $import_warnings ) ) {
				$warnings = array_merge( $warnings, $import_warnings );
			}
		}

		if ( ! empty( $warnings ) ) {
			$result['warnings'] = array_unique( $warnings );
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
		$breakpoint_props   = $widget_data['breakpoint_props'] ?? [];
		$pseudo_state_props = $widget_data['pseudo_state_props'] ?? [];
		$element_classes    = $widget_data['element_classes'] ?? [];

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

		if ( ! empty( $breakpoint_props ) ) {
			$widget = $this->styles_integrator->integrate_styles_into_widget( $widget, $breakpoint_props, $pseudo_state_props );
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
			'editor_settings' => [
				'css_converter_widget' => true,
				'disable_base_styles'  => true,
			],
			'elements'        => [ $widget ],
			'styles'          => [],
		];

		return $wrapper;
	}

	private function generate_element_id(): string {
		if ( class_exists( '\Elementor\Utils' ) && method_exists( '\Elementor\Utils', 'generate_random_string' ) ) {
			return \Elementor\Utils::generate_random_string();
		}
		return substr( md5( uniqid( '', true ) ), 0, 7 );
	}

	private function assign_element_ids_recursive( array $widgets ): array {
		$result = [];
		foreach ( $widgets as $widget ) {
			if ( empty( $widget['id'] ) ) {
				$widget['id'] = $this->generate_element_id();
			}
			if ( ! empty( $widget['elements'] ) ) {
				$widget['elements'] = $this->assign_element_ids_recursive( $widget['elements'] );
			}
			if ( ! isset( $widget['widgetType'] ) && isset( $widget['elType'] ) ) {
				$widget['widgetType'] = $widget['elType'];
			}
			$result[] = $widget;
		}
		return $result;
	}

	/**
	 * Convert desktop body CSS rules to Elementor page settings format.
	 *
	 * Only handles background/background-color, margin, and padding.
	 * All other properties are silently dropped (matching PR #32856 behaviour).
	 *
	 * @param array $breakpoint_rules Map of breakpoint name => ['property' => 'value'].
	 * @return array|null Null when no supported body rules exist; otherwise an
	 *                    associative array ready for save_page_settings().
	 */
	private function build_body_page_settings( array $breakpoint_rules ): ?array {
		$desktop = $breakpoint_rules['desktop'] ?? [];
		if ( empty( $desktop ) ) {
			return null;
		}

		$settings = [];

		// Background: background-color takes priority, then background shorthand.
		$bg_value = $desktop['background-color'] ?? ( $desktop['background'] ?? null );
		if ( null !== $bg_value ) {
			$settings['background_background'] = 'classic';
			$settings['background_color']      = trim( $bg_value );
		}

		// Margin dimensions.
		$margin = $this->build_dimensions_setting( $desktop, 'margin' );
		if ( null !== $margin ) {
			$settings['margin'] = $margin;
		}

		// Padding dimensions.
		$padding = $this->build_dimensions_setting( $desktop, 'padding' );
		if ( null !== $padding ) {
			$settings['padding'] = $padding;
		}

		return empty( $settings ) ? null : $settings;
	}

	/**
	 * Build an Elementor dimensions settings array from CSS declarations.
	 *
	 * Handles both shorthand (e.g. 'margin: 10px 20px') and individual side
	 * properties (e.g. 'margin-top: 10px').
	 *
	 * @param array  $declarations Map of CSS property => value.
	 * @param string $property     'margin' or 'padding'.
	 * @return array|null Null when no matching declarations found; otherwise
	 *                    Elementor dimensions array with top/right/bottom/left/unit/isLinked.
	 */
	private function build_dimensions_setting( array $declarations, string $property ): ?array {
		$sides = [ 'top' => null, 'right' => null, 'bottom' => null, 'left' => null ];
		$unit  = 'px';

		// Start with shorthand if present.
		if ( isset( $declarations[ $property ] ) ) {
			$expanded = $this->parse_shorthand_dimensions( $declarations[ $property ] );
			if ( null !== $expanded ) {
				$sides = $expanded['sides'];
				$unit  = $expanded['unit'];
			}
		}

		// Individual side properties override shorthand.
		$side_props = [
			'top'    => "{$property}-top",
			'right'  => "{$property}-right",
			'bottom' => "{$property}-bottom",
			'left'   => "{$property}-left",
		];

		foreach ( $side_props as $side => $prop ) {
			if ( isset( $declarations[ $prop ] ) ) {
				$parsed = $this->parse_dimension_value( $declarations[ $prop ] );
				if ( null !== $parsed ) {
					$sides[ $side ] = $parsed['value'];
					$unit           = $parsed['unit'];
				}
			}
		}

		if ( array_filter( $sides, fn( $v ) => null !== $v ) === [] ) {
			return null;
		}

		// Fill any missing sides with '0'.
		foreach ( $sides as $side => $value ) {
			if ( null === $value ) {
				$sides[ $side ] = '0';
			}
		}

		$is_linked = count( array_unique( array_values( $sides ) ) ) === 1;

		return array_merge( $sides, [ 'unit' => $unit, 'isLinked' => $is_linked ] );
	}

	/**
	 * Parse a CSS shorthand dimension value into per-side values and a unit.
	 *
	 * Handles 1–4 value shorthand following CSS box model rules.
	 * Returns null for mixed units or unsupported values.
	 *
	 * @param string $value E.g. '10px 20px' or '1rem 2rem 3rem 4rem'.
	 * @return array|null ['sides' => ['top',...], 'unit' => 'px'] or null.
	 */
	private function parse_shorthand_dimensions( string $value ): ?array {
		$parts = preg_split( '/\s+/', trim( $value ) );
		if ( empty( $parts ) || count( $parts ) > 4 ) {
			return null;
		}

		$parsed_parts = [];
		$unit         = null;

		foreach ( $parts as $part ) {
			$parsed = $this->parse_dimension_value( $part );
			if ( null === $parsed ) {
				return null;
			}
			if ( null !== $unit && $unit !== $parsed['unit'] ) {
				return null; // Mixed units not supported.
			}
			$unit           = $parsed['unit'];
			$parsed_parts[] = $parsed['value'];
		}

		$count = count( $parsed_parts );
		$top   = $parsed_parts[0];
		$right = $count >= 2 ? $parsed_parts[1] : $parsed_parts[0];
		$btm   = $count >= 3 ? $parsed_parts[2] : $parsed_parts[0];
		$left  = $count >= 4 ? $parsed_parts[3] : $right;

		return [
			'sides' => [ 'top' => $top, 'right' => $right, 'bottom' => $btm, 'left' => $left ],
			'unit'  => $unit ?? 'px',
		];
	}

	/**
	 * Parse a single CSS dimension token into a numeric string and unit.
	 *
	 * @param string $value E.g. '10px', '1.5em', '0'.
	 * @return array|null ['value' => '10', 'unit' => 'px'] or null for unsupported values.
	 */
	private function parse_dimension_value( string $value ): ?array {
		$value = trim( $value );

		if ( '0' === $value ) {
			return [ 'value' => '0', 'unit' => 'px' ];
		}

		if ( preg_match( '/^(-?[\d.]+)(px|em|rem|%|vh|vw|vmin|vmax)$/', $value, $m ) ) {
			return [ 'value' => $m[1], 'unit' => $m[2] ];
		}

		return null;
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
	 * Build variable fallback map for substituting unresolved var() references.
	 *
	 * Includes all variable definitions from css_variables param and extracted CSS.
	 * Applies renames so that variables renamed during import (e.g. --primary -> --primary-1)
	 * can still be resolved when the CSS has been updated to use the new names.
	 *
	 * @param string $css_variables   Raw CSS variable declarations from request.
	 * @param string $extracted_css   CSS extracted from HTML style tags.
	 * @param array  $variable_renames Map of original name => renamed name.
	 * @return array<string, string> Map of --variable-name => literal value.
	 */
	private function build_variable_fallback_map( string $css_variables, string $extracted_css, array $variable_renames = [] ): array {
		$map       = Variable_Fallback_Substitutor::build_fallback_map_from_css( $css_variables );
		$from_html = Variable_Fallback_Substitutor::build_fallback_map_from_css( $extracted_css );
		$map       = array_merge( $map, $from_html );

		foreach ( $variable_renames as $original => $renamed ) {
			$original_with_dashes = str_starts_with( $original, '--' ) ? $original : '--' . $original;
			$renamed_with_dashes  = str_starts_with( $renamed, '--' ) ? $renamed : '--' . $renamed;
			if ( isset( $map[ $original_with_dashes ] ) ) {
				$map[ $renamed_with_dashes ] = $map[ $original_with_dashes ];
			}
		}

		return $map;
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

		$conversion_result = Variable_Conversion_Service::convert_to_editor_variables( $raw_vars );
		$converted         = $conversion_result['converted'] ?? [];

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

		$extractor = new Class_Extractor();
		$breakpoint_matcher = new Breakpoint_Matcher();
		$extracted_classes = $extractor->extract_from_css( $css, $breakpoint_matcher );

		if ( empty( $extracted_classes ) ) {
			return $result;
		}

		$classes_to_import = [];
		foreach ( $extracted_classes as $class_name => $breakpoint_data ) {
			if ( in_array( $class_name, $used_classes, true ) ) {
				$classes_to_import[ $class_name ] = $breakpoint_data;
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
		$conversion_result  = $conversion_service->convert_to_atomic( $classes_to_import );
		$converted_classes  = $conversion_result['classes'] ?? [];

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

