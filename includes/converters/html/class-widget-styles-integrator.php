<?php
/**
 * Widget Styles Integrator
 *
 * Integrates atomic props as styles into widget structures.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

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
	 * Constructor.
	 */
	public function __construct() {
		$this->class_generator = new Atomic_Widget_Class_Generator();
	}

	/**
	 * Integrate styles into a widget.
	 *
	 * @param array $widget       Widget JSON structure.
	 * @param array $atomic_props Atomic properties to integrate.
	 * @return array Widget with integrated styles.
	 */
	public function integrate_styles_into_widget( array $widget, array $atomic_props ): array {
		if ( empty( $atomic_props ) ) {
			return $widget;
		}

		$widget_type = $widget['widgetType'] ?? $widget['elType'] ?? '';
		$class_id    = $this->class_generator->generate_class_id( $widget_type );

		$styles = $this->create_styles_structure( $class_id, $atomic_props );

		$widget['styles'] = $styles;

		$widget = $this->add_class_reference_to_widget( $widget, $class_id );

		return $widget;
	}

	/**
	 * Integrate styles into multiple widgets.
	 *
	 * @param array $widgets              Array of widgets.
	 * @param array $widgets_atomic_props Array of atomic props per widget.
	 * @return array Widgets with integrated styles.
	 */
	public function integrate_styles_into_multiple_widgets( array $widgets, array $widgets_atomic_props ): array {
		$processed_widgets = [];

		foreach ( $widgets as $index => $widget ) {
			$atomic_props        = $widgets_atomic_props[ $index ] ?? [];
			$processed_widgets[] = $this->integrate_styles_into_widget( $widget, $atomic_props );
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
	 * Create styles structure for a widget.
	 *
	 * @param string $class_id     Class ID.
	 * @param array  $atomic_props Atomic properties.
	 * @return array Styles structure.
	 */
	private function create_styles_structure( string $class_id, array $atomic_props ): array {
		return [
			$class_id => [
				'id'       => $class_id,
				'label'    => 'local',
				'type'     => 'class',
				'variants' => [
					[
						'meta'       => [
							'breakpoint' => 'desktop',
							'state'      => null,
						],
						'props'      => $atomic_props,
						'custom_css' => null,
					],
				],
			],
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
	 * Extract atomic props from widget data.
	 *
	 * @param array $widget_data Widget data array.
	 * @return array Atomic props.
	 */
	public function extract_atomic_props_from_widget_data( array $widget_data ): array {
		return $widget_data['atomic_props'] ?? [];
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
