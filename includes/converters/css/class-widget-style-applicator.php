<?php
/**
 * Widget Style Applicator Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use ElementorHtmlCssConverter\Converters\Classes\Elementor_Document_Service;
use ElementorHtmlCssConverter\Converters\Css\Widget_Style_Applicator_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Style_Applicator
 *
 * Applies CSS styles to Elementor atomic widgets by converting CSS to atomic
 * format and adding it to the widget's styles property.
 */
class Widget_Style_Applicator implements Widget_Style_Applicator_Interface {
	/**
	 * The CSS converter.
	 *
	 * @var Css_Converter
	 */
	private Css_Converter $css_converter;

	/**
	 * The style definition builder.
	 *
	 * @var Style_Definition_Builder
	 */
	private Style_Definition_Builder $style_builder;

	/**
	 * The Elementor document service.
	 *
	 * @var Elementor_Document_Service
	 */
	private Elementor_Document_Service $document_service;

	/**
	 * Constructor.
	 *
	 * @param Css_Converter              $css_converter    The CSS converter.
	 * @param Style_Definition_Builder   $style_builder    The style definition builder.
	 * @param Elementor_Document_Service $document_service The document service.
	 */
	public function __construct(
		Css_Converter $css_converter,
		Style_Definition_Builder $style_builder,
		Elementor_Document_Service $document_service
	) {
		$this->css_converter    = $css_converter;
		$this->style_builder    = $style_builder;
		$this->document_service = $document_service;
	}

	/**
	 * Apply styles to an existing widget in a post.
	 *
	 * @param int    $post_id    The post ID.
	 * @param string $widget_id  The widget ID.
	 * @param string $css_string The CSS styles to apply.
	 * @return array Result with success, postId, widgetId, stylesApplied, customCss.
	 */
	public function apply_to_existing( int $post_id, string $widget_id, string $css_string ): array {
		$widget = $this->document_service->find_widget( $post_id, $widget_id );

		if ( ! $widget ) {
			return $this->create_error_result( 'Widget not found' );
		}

		if ( $this->is_empty_css( $css_string ) ) {
			return $this->create_post_success_result( $post_id, $widget_id, [], '' );
		}

		$conversion_result = $this->convert_css_to_atomic( $css_string );

		if ( $this->has_no_converted_props( $conversion_result ) ) {
			return $this->create_post_success_result(
				$post_id,
				$widget_id,
				[],
				$conversion_result['customCss'] ?? ''
			);
		}

		$styles_applied = $conversion_result['props'];
		$custom_css = $conversion_result['customCss'] ?? '';

		$existing_local_style = $this->find_existing_local_style( $widget );
		$style_builder        = $this->style_builder;
		$applicator           = $this;

		$update_callback = function( $widget_data ) use ( $styles_applied, $existing_local_style, $style_builder, $applicator, $widget_id, $custom_css ) {
			if ( ! isset( $widget_data['styles'] ) ) {
				$widget_data['styles'] = [];
			}

			if ( null !== $existing_local_style ) {
				$widget_data = $this->merge_existing_style( $widget_data, $existing_local_style['id'], $styles_applied );
			} else {
				$style_definition = $style_builder->build( $styles_applied, $widget_id );
				$style_id         = $style_definition['id'];

				$widget_data['styles'][ $style_id ] = $style_definition;
				$widget_data = $applicator->add_class_reference_to_widget( $widget_data, $style_id );
			}

			if ( ! empty( $custom_css ) ) {
				if ( ! isset( $widget_data['settings'] ) ) {
					$widget_data['settings'] = [];
				}
				$widget_data['settings']['custom_css'] = $custom_css;
			}

			return $widget_data;
		};

		$success = $this->document_service->update_widget( $post_id, $widget_id, $update_callback );

		if ( ! $success ) {
			return $this->create_error_result( 'Failed to save widget' );
		}

		return $this->create_post_success_result(
			$post_id,
			$widget_id,
			$styles_applied,
			$conversion_result['customCss'] ?? ''
		);
	}

	/**
	 * Find an existing "local" style in the widget.
	 *
	 * @param array $widget The widget data.
	 * @return array|null The existing local style or null if not found.
	 */
	private function find_existing_local_style( array $widget ): ?array {
		if ( empty( $widget['styles'] ) || ! is_array( $widget['styles'] ) ) {
			return null;
		}

		foreach ( $widget['styles'] as $style ) {
			if ( isset( $style['label'] ) && 'local' === $style['label'] ) {
				return $style;
			}
		}

		return null;
	}

	/**
	 * Create a new post with a styled widget.
	 *
	 * @param string $title           The post title.
	 * @param string $post_status     The post status.
	 * @param string $widget_type     The widget type.
	 * @param array  $widget_settings The widget settings.
	 * @param string $css_string      The CSS styles to apply.
	 * @return array Result with success, postId, widgetId, editUrl.
	 */
	public function create_post_with_widget(
		string $title,
		string $post_status,
		string $widget_type,
		array $widget_settings,
		string $css_string
	): array {
		$post_result = $this->document_service->create_post( $title, $post_status );

		if ( ! $post_result ) {
			return $this->create_error_result( 'Failed to create post' );
		}

		$post_id   = $post_result['post_id'];
		$widget_id = $this->document_service->generate_element_id();

		$widget_data = $this->create_widget_data( $widget_id, $widget_type, $widget_settings, $css_string );

		$success = $this->document_service->save_with_widget( $post_id, $widget_data );

		if ( ! $success ) {
			return $this->create_error_result( 'Failed to save widget to post' );
		}

		return [
			'success'  => true,
			'postId'   => $post_id,
			'widgetId' => $widget_id,
			'editUrl'  => $this->document_service->get_edit_url( $post_id ),
		];
	}

	/**
	 * Add a new styled widget to an existing post.
	 *
	 * @param int    $post_id         The post ID.
	 * @param string $widget_type     The widget type.
	 * @param array  $widget_settings The widget settings.
	 * @param string $css_string      The CSS styles to apply.
	 * @return array Result with success, postId, widgetId.
	 */
	public function add_widget_to_post(
		int $post_id,
		string $widget_type,
		array $widget_settings,
		string $css_string
	): array {
		$widget_id   = $this->document_service->generate_element_id();
		$widget_data = $this->create_widget_data( $widget_id, $widget_type, $widget_settings, $css_string );

		$result_id = $this->document_service->add_widget( $post_id, $widget_data );

		if ( ! $result_id ) {
			return $this->create_error_result( 'Failed to add widget to post' );
		}

		return [
			'success'  => true,
			'postId'   => $post_id,
			'widgetId' => $result_id,
		];
	}

	/**
	 * Create widget data with styles applied.
	 *
	 * @param string $widget_id       The widget ID.
	 * @param string $widget_type     The widget type.
	 * @param array  $widget_settings The widget settings.
	 * @param string $css_string      The CSS styles.
	 * @return array The complete widget data.
	 */
	private function create_widget_data(
		string $widget_id,
		string $widget_type,
		array $widget_settings,
		string $css_string
	): array {
		$widget = [
			'id'         => $widget_id,
			'elType'     => 'widget',
			'widgetType' => $widget_type,
			'settings'   => $widget_settings,
			'styles'     => [],
		];

		if ( ! $this->is_empty_css( $css_string ) ) {
			$conversion_result = $this->convert_css_to_atomic( $css_string );

			if ( ! $this->has_no_converted_props( $conversion_result ) ) {
				$style_definition = $this->style_builder->build( $conversion_result['props'], $widget_id );
				$style_id = $style_definition['id'];

				$widget['styles'][ $style_id ] = $style_definition;

				$widget = $this->add_class_reference_to_widget( $widget, $style_id );
			}

			$custom_css = $conversion_result['customCss'] ?? '';
			if ( ! empty( $custom_css ) ) {
				$widget['settings']['custom_css'] = $custom_css;
			}
		}

		return $widget;
	}

	/**
	 * Create error result.
	 *
	 * @param string $message The error message.
	 * @return array The error result.
	 */
	private function create_error_result( string $message ): array {
		return [
			'success' => false,
			'error'   => $message,
		];
	}

	/**
	 * Create success result for post operations.
	 *
	 * @param int    $post_id        The post ID.
	 * @param string $widget_id      The widget ID.
	 * @param array  $styles_applied The styles that were applied.
	 * @param string $custom_css     Any unsupported CSS.
	 * @return array The success result.
	 */
	private function create_post_success_result(
		int $post_id,
		string $widget_id,
		array $styles_applied,
		string $custom_css
	): array {
		return [
			'success'       => true,
			'postId'        => $post_id,
			'widgetId'      => $widget_id,
			'stylesApplied' => $styles_applied,
			'customCss'     => $custom_css,
		];
	}

	/**
	 * Apply CSS styles to a widget.
	 *
	 * @param array  $widget     The widget data structure.
	 * @param string $css_string The CSS styles to apply.
	 * @return array Result with 'success', 'widget', and 'customCss' keys.
	 */
	public function apply( array $widget, string $css_string ): array {
		if ( $this->is_empty_css( $css_string ) ) {
			return $this->create_success_result( $widget, '' );
		}

		$conversion_result = $this->convert_css_to_atomic( $css_string );

		$custom_css = $conversion_result['customCss'] ?? '';

		if ( $this->has_no_converted_props( $conversion_result ) ) {
			if ( ! empty( $custom_css ) ) {
				if ( ! isset( $widget['settings'] ) ) {
					$widget['settings'] = [];
				}
				$widget['settings']['custom_css'] = $custom_css;
			}
			return $this->create_success_result( $widget, $custom_css );
		}

		$widget = $this->add_styles_to_widget( $widget, $conversion_result['props'] );

		if ( ! empty( $custom_css ) ) {
			if ( ! isset( $widget['settings'] ) ) {
				$widget['settings'] = [];
			}
			$widget['settings']['custom_css'] = $custom_css;
		}

		return $this->create_success_result( $widget, $custom_css );
	}

	/**
	 * Check if CSS string is empty.
	 *
	 * @param string $css_string The CSS string.
	 * @return bool True if empty.
	 */
	private function is_empty_css( string $css_string ): bool {
		return empty( trim( $css_string ) );
	}

	/**
	 * Convert CSS string to atomic format.
	 *
	 * @param string $css_string The CSS string.
	 * @return array The conversion result.
	 */
	private function convert_css_to_atomic( string $css_string ): array {
		return $this->css_converter->convert( [ 'cssString' => $css_string ] );
	}

	/**
	 * Check if conversion result has no props.
	 *
	 * @param array $conversion_result The conversion result.
	 * @return bool True if no props were converted.
	 */
	private function has_no_converted_props( array $conversion_result ): bool {
		return empty( $conversion_result['props'] );
	}

	/**
	 * Add styles to widget.
	 *
	 * Reuses existing "local" style if present, otherwise creates a new one.
	 *
	 * @param array $widget       The widget data.
	 * @param array $atomic_props The converted atomic properties.
	 * @return array The widget with added styles.
	 */
	private function add_styles_to_widget( array $widget, array $atomic_props ): array {
		$widget = $this->ensure_styles_property( $widget );

		$existing_local_style = $this->find_existing_local_style( $widget );

		if ( null !== $existing_local_style ) {
			$widget = $this->merge_existing_style( $widget, $existing_local_style['id'], $atomic_props );
		} else {
			$widget_id        = $this->get_widget_id( $widget );
			$style_definition = $this->style_builder->build( $atomic_props, $widget_id );
			$style_id         = $style_definition['id'];

			$widget['styles'][ $style_id ] = $style_definition;

			$widget = $this->add_class_reference_to_widget( $widget, $style_id );
		}

		return $widget;
	}

	/**
	 * Get widget ID, generating one if not present.
	 *
	 * @param array $widget The widget data.
	 * @return string The widget ID.
	 */
	private function get_widget_id( array $widget ): string {
		return $widget['id'] ?? $this->generate_unique_id();
	}

	/**
	 * Generate a unique ID.
	 *
	 * @return string A unique ID.
	 */
	private function generate_unique_id(): string {
		return uniqid( '', true );
	}

	/**
	 * Ensure widget has styles property.
	 *
	 * @param array $widget The widget data.
	 * @return array The widget with styles property.
	 */
	private function ensure_styles_property( array $widget ): array {
		if ( ! isset( $widget['styles'] ) ) {
			$widget['styles'] = [];
		}
		return $widget;
	}

	/**
	 * Add class reference to widget settings.
	 *
	 * For the style CSS to be applied to the widget's HTML element,
	 * the style's class ID must be added to the widget's classes array.
	 *
	 * @param array  $widget   The widget data.
	 * @param string $class_id The class ID to add.
	 * @return array The widget with class reference added.
	 */
	public function add_class_reference_to_widget( array $widget, string $class_id ): array {
		if ( ! isset( $widget['settings'] ) ) {
			$widget['settings'] = [];
		}

		if ( ! isset( $widget['settings']['classes'] ) ) {
			$widget['settings']['classes'] = [
				'$$type' => 'classes',
				'value'  => [],
			];
		}

		if ( ! isset( $widget['settings']['classes']['value'] ) ) {
			$existing = $widget['settings']['classes'];
			$widget['settings']['classes'] = [
				'$$type' => 'classes',
				'value'  => is_array( $existing ) ? $existing : [],
			];
		}

		if ( ! in_array( $class_id, $widget['settings']['classes']['value'], true ) ) {
			$widget['settings']['classes']['value'][] = $class_id;
		}

		return $widget;
	}

	/**
	 * Create success result.
	 *
	 * @param array  $widget     The widget data.
	 * @param string $custom_css Any unsupported CSS.
	 * @return array The result array.
	 */
	private function merge_existing_style( array $widget, string $style_id, array $styles_applied ): array {
		if ( isset( $widget['styles'][ $style_id ]['variants'][0]['props'] ) ) {
			$existing_props = $widget['styles'][ $style_id ]['variants'][0]['props'];
			$merged_props   = array_merge( $existing_props, $styles_applied );
			$widget['styles'][ $style_id ]['variants'][0]['props'] = $merged_props;
		} else {
			$widget['styles'][ $style_id ]['variants'][0]['props'] = $styles_applied;
		}

		return $widget;
	}

	private function create_success_result( array $widget, string $custom_css ): array {
		return [
			'success'   => true,
			'widget'    => $widget,
			'customCss' => $custom_css,
		];
	}
}
