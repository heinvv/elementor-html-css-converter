<?php
/**
 * Atomic Widget JSON Creator
 *
 * Creates widget JSON structures using Elementor's Widget_Builder and Element_Builder.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Atomic_Widget_JSON_Creator
 *
 * Creates Elementor widget JSON structures from parsed HTML data.
 */
class Atomic_Widget_JSON_Creator {

	/**
	 * Widget mapper instance.
	 *
	 * @var HTML_To_Atomic_Widget_Mapper
	 */
	private HTML_To_Atomic_Widget_Mapper $widget_mapper;

	/**
	 * Settings preparer instance.
	 *
	 * @var Atomic_Widget_Settings_Preparer
	 */
	private Atomic_Widget_Settings_Preparer $settings_preparer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_mapper     = new HTML_To_Atomic_Widget_Mapper();
		$this->settings_preparer = new Atomic_Widget_Settings_Preparer();
	}

	/**
	 * Create widget JSON from widget data.
	 *
	 * @param array $widget_data Widget data array.
	 * @return array|null Widget JSON or null on failure.
	 */
	public function create_widget_json( array $widget_data ): ?array {
		if ( ! $this->is_atomic_widgets_available() ) {
			return null;
		}

		$widget_type = $widget_data['widget_type'] ?? '';
		if ( empty( $widget_type ) ) {
			return null;
		}

		$atomic_props = $widget_data['atomic_props'] ?? [];
		$content      = $widget_data['content'] ?? '';
		$attributes   = $widget_data['attributes'] ?? [];
		$children     = $widget_data['children'] ?? [];

		$settings = $this->settings_preparer->prepare_widget_settings(
			$widget_type,
			$atomic_props,
			$content,
			$attributes
		);

		if ( $this->widget_mapper->is_container_widget( $widget_type ) ) {
			return $this->create_container_widget( $widget_type, $settings, $children );
		}

		return $this->create_content_widget( $widget_type, $settings );
	}

	/**
	 * Create multiple widgets from array of widget data.
	 *
	 * @param array $widgets_data Array of widget data.
	 * @return array Array of widget JSON structures.
	 */
	public function create_multiple_widgets( array $widgets_data ): array {
		$widgets = [];

		foreach ( $widgets_data as $widget_data ) {
			$widget = $this->create_widget_json( $widget_data );
			if ( $widget ) {
				$widgets[] = $widget;
			}
		}

		return $widgets;
	}

	/**
	 * Create a content widget (non-container).
	 *
	 * @param string $widget_type Widget type.
	 * @param array  $settings    Widget settings.
	 * @return array|null Widget JSON or null.
	 */
	private function create_content_widget( string $widget_type, array $settings ): ?array {
		if ( ! class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Widget_Builder' ) ) {
			return null;
		}

		try {
			$widget_builder = \Elementor\Modules\AtomicWidgets\Elements\Base\Widget_Builder::make( $widget_type );

			return $widget_builder
				->settings( $settings )
				->is_locked( false )
				->editor_settings( [] )
				->build();

		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Create a container widget with children.
	 *
	 * @param string $widget_type Widget type.
	 * @param array  $settings    Widget settings.
	 * @param array  $children    Child widget data.
	 * @return array|null Widget JSON or null.
	 */
	private function create_container_widget( string $widget_type, array $settings, array $children ): ?array {
		if ( ! class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Element_Builder' ) ) {
			return null;
		}

		$child_widgets = $this->create_child_widgets( $children );

		try {
			$element_builder = \Elementor\Modules\AtomicWidgets\Elements\Base\Element_Builder::make( $widget_type );

			return $element_builder
				->settings( $settings )
				->children( $child_widgets )
				->is_locked( false )
				->editor_settings( [] )
				->build();

		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Recursively create child widgets.
	 *
	 * @param array $children Array of child widget data.
	 * @return array Array of child widget JSON structures.
	 */
	private function create_child_widgets( array $children ): array {
		$child_widgets = [];

		foreach ( $children as $child_data ) {
			$child_widget = $this->create_widget_json( $child_data );
			if ( $child_widget ) {
				$child_widgets[] = $child_widget;
			}
		}

		return $child_widgets;
	}

	/**
	 * Check if atomic widgets module is available.
	 *
	 * @return bool True if available.
	 */
	private function is_atomic_widgets_available(): bool {
		return class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Widget_Builder' ) &&
				class_exists( 'Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Element_Builder' );
	}

	/**
	 * Get supported widget types.
	 *
	 * @return array List of supported widget types.
	 */
	public function get_supported_widget_types(): array {
		return $this->widget_mapper->get_widget_types();
	}

	/**
	 * Check if widget type is supported.
	 *
	 * @param string $widget_type Widget type to check.
	 * @return bool True if supported.
	 */
	public function is_widget_type_supported( string $widget_type ): bool {
		$supported_types = $this->get_supported_widget_types();
		return in_array( $widget_type, $supported_types, true );
	}
}
