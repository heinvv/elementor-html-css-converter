<?php
/**
 * Widget Styles Integrator
 *
 * Integrates atomic props as styles into widget structures.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

use ElementorHtmlCssConverter\Converters\Css\Style_Definition_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Styles_Integrator
 *
 * Integrates atomic properties as style definitions into widgets.
 */
class Widget_Styles_Integrator {

	/**
	 * Class generator instance.
	 *
	 * @var Atomic_Widget_Class_Generator
	 */
	private Atomic_Widget_Class_Generator $class_generator;

	/**
	 * Style definition builder instance.
	 *
	 * @var Style_Definition_Builder
	 */
	private Style_Definition_Builder $style_builder;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->class_generator = new Atomic_Widget_Class_Generator();
		$this->style_builder   = new Style_Definition_Builder();
	}

	/**
	 * Integrate styles with breakpoints into a widget.
	 *
	 * @param array $widget          Widget JSON structure.
	 * @param array $breakpoint_props Breakpoint-aware atomic properties.
	 * @return array Widget with integrated styles.
	 */
	public function integrate_styles_into_widget( array $widget, array $breakpoint_props ): array {
		if ( empty( $breakpoint_props ) ) {
			return $widget;
		}

		$widget_type = $widget['widgetType'] ?? $widget['elType'] ?? '';
		$class_id    = $this->class_generator->generate_class_id( $widget_type );

		$styles = $this->create_styles_structure_with_breakpoints( $class_id, $breakpoint_props );

		if ( ! empty( $styles ) ) {
			$widget['styles'] = $styles;
			$widget           = $this->add_class_reference_to_widget( $widget, $class_id );
		}

		$widget = $this->apply_custom_css_to_widget_settings( $widget, $breakpoint_props );

		return $widget;
	}

	/**
	 * Integrate styles into multiple widgets.
	 *
	 * @param array $widgets                Array of widgets.
	 * @param array $widgets_breakpoint_props Array of breakpoint props per widget.
	 * @return array Widgets with integrated styles.
	 */
	public function integrate_styles_into_multiple_widgets( array $widgets, array $widgets_breakpoint_props ): array {
		$processed_widgets = [];

		foreach ( $widgets as $index => $widget ) {
			$breakpoint_props   = $widgets_breakpoint_props[ $index ] ?? [];
			$processed_widgets[] = $this->integrate_styles_into_widget( $widget, $breakpoint_props );
		}

		return $processed_widgets;
	}

	/**
	 * Create global classes from widgets.
	 *
	 * @param array $widgets Array of widgets with styles.
	 * @return array Global classes structure.
	 */
	public function create_global_classes_from_widgets( array $widgets ): array {
		$global_classes = [
			'items' => [],
			'order' => [],
		];

		foreach ( $widgets as $widget ) {
			if ( isset( $widget['styles'] ) && ! empty( $widget['styles'] ) ) {
				foreach ( $widget['styles'] as $class_id => $style_definition ) {
					$global_classes['items'][ $class_id ] = $style_definition;
					$global_classes['order'][]            = $class_id;
				}
			}
		}

		return $global_classes;
	}

	/**
	 * Create styles structure with breakpoints for a widget.
	 *
	 * @param string $class_id         Class ID.
	 * @param array  $breakpoint_props Breakpoint-aware atomic properties.
	 *                                  Format: ['desktop' => [...], 'tablet' => [...], 'mobile' => [...]]
	 * @return array Styles structure.
	 */
	private function create_styles_structure_with_breakpoints( string $class_id, array $breakpoint_props ): array {
		if ( empty( $breakpoint_props ) ) {
			return [];
		}

		$style_definition = $this->style_builder->build_with_breakpoints( $breakpoint_props, $class_id );

		if ( empty( $style_definition ) ) {
			return [];
		}

		return [
			$class_id => $style_definition,
		];
	}

	/**
	 * Add class reference to widget settings.
	 *
	 * @param array  $widget   Widget structure.
	 * @param string $class_id Class ID to add.
	 * @return array Widget with class reference.
	 */
	private function add_class_reference_to_widget( array $widget, string $class_id ): array {
		if ( ! isset( $widget['settings'] ) ) {
			$widget['settings'] = [];
		}

		if ( ! isset( $widget['settings']['classes'] ) ) {
			$widget['settings']['classes'] = [
				'$$type' => 'classes',
				'value'  => [],
			];
		}

		if ( ! in_array( $class_id, $widget['settings']['classes']['value'], true ) ) {
			$widget['settings']['classes']['value'][] = $class_id;
		}

		return $widget;
	}

	/**
	 * Extract breakpoint props from widget data.
	 *
	 * @param array $widget_data Widget data array.
	 * @return array Breakpoint props.
	 */
	public function extract_breakpoint_props_from_widget_data( array $widget_data ): array {
		return $widget_data['breakpoint_props'] ?? [];
	}

	/**
	 * Apply custom CSS from breakpoint_props to widget settings.
	 *
	 * Merges custom_css from all breakpoints into widget settings.
	 *
	 * @param array $widget          Widget structure.
	 * @param array $breakpoint_props Breakpoint-aware properties with custom_css.
	 * @return array Widget with custom_css applied to settings.
	 */
	private function apply_custom_css_to_widget_settings( array $widget, array $breakpoint_props ): array {
		$all_custom_css = [];

		foreach ( $breakpoint_props as $breakpoint => $breakpoint_data ) {
			if ( is_array( $breakpoint_data ) && isset( $breakpoint_data['custom_css'] ) && ! empty( $breakpoint_data['custom_css'] ) ) {
				$all_custom_css[] = $breakpoint_data['custom_css'];
			}
		}

		if ( empty( $all_custom_css ) ) {
			return $widget;
		}

		if ( ! isset( $widget['settings'] ) ) {
			$widget['settings'] = [];
		}

		$merged_custom_css = implode( "\n", $all_custom_css );
		$existing_custom_css = $widget['settings']['custom_css'] ?? '';

		if ( ! empty( $existing_custom_css ) ) {
			$widget['settings']['custom_css'] = $existing_custom_css . "\n" . $merged_custom_css;
		} else {
			$widget['settings']['custom_css'] = $merged_custom_css;
		}

		return $widget;
	}

	/**
	 * Get the class generator instance.
	 *
	 * @return Atomic_Widget_Class_Generator Class generator.
	 */
	public function get_class_generator(): Atomic_Widget_Class_Generator {
		return $this->class_generator;
	}
}
