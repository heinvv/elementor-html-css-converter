<?php
/**
 * HTML Converter
 *
 * Main orchestrator for HTML to Elementor atomic widget conversion.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Core;

use ElementorHtmlCssConverter\Converters\Html\Atomic_Data_Parser;
use ElementorHtmlCssConverter\Converters\Html\Atomic_Widget_JSON_Creator;
use ElementorHtmlCssConverter\Converters\Html\Widget_Styles_Integrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Html_Converter
 *
 * Orchestrates the conversion of HTML to Elementor atomic widgets.
 */
class Html_Converter {

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
	 * Constructor.
	 *
	 * @param Converter_Registry $converter_registry CSS converter registry.
	 */
	public function __construct( Converter_Registry $converter_registry ) {
		$this->data_parser       = new Atomic_Data_Parser( $converter_registry );
		$this->json_creator      = new Atomic_Widget_JSON_Creator();
		$this->styles_integrator = new Widget_Styles_Integrator();
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

		$widget_data_array = $this->data_parser->parse_html_for_atomic_widgets( $html );

		if ( empty( $widget_data_array ) ) {
			return $this->build_error_result( 'No supported HTML elements found' );
		}

		$widgets = $this->create_widgets( $widget_data_array );

		if ( empty( $widgets ) ) {
			return $this->build_error_result( 'No widgets could be created from the HTML' );
		}

		$widgets_with_styles = $this->integrate_styles( $widgets, $widget_data_array );

		// Wrap non-container top-level widgets in div containers.
		$wrapped_widgets = $this->wrap_non_container_widgets( $widgets_with_styles );

		return $this->build_success_result( $wrapped_widgets );
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
	 * @return array Widgets with integrated styles.
	 */
	private function integrate_styles( array $widgets, array $widget_data_array ): array {
		$widgets_with_styles = [];

		foreach ( $widgets as $index => $widget ) {
			$widget_data = $widget_data_array[ $index ] ?? [];
			$widget      = $this->integrate_styles_recursive( $widget, $widget_data );

			$widgets_with_styles[] = $widget;
		}

		return $widgets_with_styles;
	}

	/**
	 * Recursively integrate styles into a widget and its children.
	 *
	 * @param array $widget      Widget JSON structure.
	 * @param array $widget_data Original widget data with atomic props and children.
	 * @return array Widget with integrated styles.
	 */
	private function integrate_styles_recursive( array $widget, array $widget_data ): array {
		$atomic_props = $widget_data['atomic_props'] ?? [];

		// Integrate styles for this widget.
		if ( ! empty( $atomic_props ) ) {
			$widget = $this->styles_integrator->integrate_styles_into_widget( $widget, $atomic_props );
		}

		// Recursively process children.
		if ( ! empty( $widget['elements'] ) && ! empty( $widget_data['children'] ) ) {
			$processed_children = [];

			foreach ( $widget['elements'] as $child_index => $child_widget ) {
				$child_data           = $widget_data['children'][ $child_index ] ?? [];
				$processed_children[] = $this->integrate_styles_recursive( $child_widget, $child_data );
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
				// Already a container, keep as-is.
				$wrapped_widgets[] = $widget;
			} else {
				// Wrap non-container in a div-block.
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

		// Check elType (for containers built with Element_Builder).
		$el_type = $widget['elType'] ?? '';
		if ( in_array( $el_type, $container_types, true ) ) {
			return true;
		}

		// Check widgetType.
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
		// Create a minimal e-div-block container.
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
		// Check if Elementor is loaded.
		if ( ! class_exists( 'Elementor\\Plugin' ) ) {
			return [
				'available' => false,
				'reason'    => 'Elementor plugin is not active',
			];
		}

		// Check if Elementor instance exists.
		if ( ! isset( \Elementor\Plugin::$instance ) || ! \Elementor\Plugin::$instance ) {
			return [
				'available' => false,
				'reason'    => 'Elementor not fully initialized',
			];
		}

		// Check if experiments system exists.
		if ( ! isset( \Elementor\Plugin::$instance->experiments ) ) {
			return [
				'available' => false,
				'reason'    => 'Elementor experiments system not available',
			];
		}

		// Check if atomic elements experiment is active.
		$experiment_active = \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_atomic_elements' );
		if ( ! $experiment_active ) {
			return [
				'available' => false,
				'reason'    => 'Atomic Elements experiment is not enabled. Enable it in Elementor > Settings > Features > Atomic Elements',
			];
		}

		// Check if the required classes exist.
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
