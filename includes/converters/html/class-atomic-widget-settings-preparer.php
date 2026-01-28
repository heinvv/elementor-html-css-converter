<?php
/**
 * Atomic Widget Settings Preparer
 *
 * Prepares widget settings based on widget type and element data.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Atomic_Widget_Settings_Preparer
 *
 * Prepares settings for atomic widgets based on HTML element data.
 */
class Atomic_Widget_Settings_Preparer {

	/**
	 * Widget mapper instance.
	 *
	 * @var HTML_To_Atomic_Widget_Mapper
	 */
	private HTML_To_Atomic_Widget_Mapper $widget_mapper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_mapper = new HTML_To_Atomic_Widget_Mapper();
	}

	/**
	 * Prepare widget settings.
	 *
	 * @param string $widget_type  The widget type.
	 * @param array  $atomic_props Atomic properties from CSS conversion.
	 * @param string $content      Text content of the element.
	 * @param array  $attributes   HTML attributes.
	 * @return array Prepared settings.
	 */
	public function prepare_widget_settings( string $widget_type, array $atomic_props, string $content, array $attributes = [] ): array {
		$settings = [];

		$settings = $this->add_content_settings( $settings, $widget_type, $content, $attributes );

		foreach ( $atomic_props as $prop_name => $atomic_prop ) {
			$settings[ $prop_name ] = $atomic_prop;
		}

		$settings = $this->add_default_settings( $settings, $widget_type );

		$settings['classes'] = [
			'$$type' => 'classes',
			'value'  => [],
		];

		if ( ! empty( $attributes ) ) {
			$filtered_attributes = $this->filter_attributes( $attributes );
			if ( ! empty( $filtered_attributes ) ) {
				$settings['attributes'] = $filtered_attributes;
			}
		}

		return $settings;
	}

	/**
	 * Add content-specific settings based on widget type.
	 *
	 * @param array  $settings   Current settings.
	 * @param string $widget_type Widget type.
	 * @param string $content    Text content.
	 * @param array  $attributes HTML attributes.
	 * @return array Updated settings.
	 */
	private function add_content_settings( array $settings, string $widget_type, string $content, array $attributes ): array {
		switch ( $widget_type ) {
			case 'e-heading':
				$settings['title'] = $content;
				$settings['tag']   = $this->create_atomic_prop( 'string', $this->extract_heading_tag( $attributes ) );
				$settings['level'] = $this->extract_heading_level( $attributes );
				break;

			case 'e-paragraph':
				$settings['text'] = $content;
				break;

			case 'e-button':
				$settings['text'] = $content;
				if ( isset( $attributes['href'] ) ) {
					$settings['link'] = $this->create_link_prop( $attributes['href'], $attributes );
				}
				break;

			case 'e-image':
				$settings['src'] = $this->create_image_src_prop( $attributes['src'] ?? '' );
				$settings['alt'] = $attributes['alt'] ?? '';
				if ( isset( $attributes['width'] ) ) {
					$settings['width'] = $this->create_atomic_prop( 'string', $attributes['width'] );
				}
				if ( isset( $attributes['height'] ) ) {
					$settings['height'] = $this->create_atomic_prop( 'string', $attributes['height'] );
				}
				break;

			case 'e-flexbox':
				break;
		}

		return $settings;
	}

	/**
	 * Add default settings based on widget type.
	 *
	 * @param array  $settings    Current settings.
	 * @param string $widget_type Widget type.
	 * @return array Updated settings.
	 */
	private function add_default_settings( array $settings, string $widget_type ): array {
		if ( 'e-flexbox' === $widget_type ) {
			$default_flexbox = $this->widget_mapper->get_default_flexbox_settings();
			foreach ( $default_flexbox as $key => $value ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = $value;
				}
			}
		}

		return $settings;
	}

	/**
	 * Create an atomic prop structure.
	 *
	 * @param string $type  Prop type.
	 * @param mixed  $value Prop value.
	 * @return array Atomic prop structure.
	 */
	private function create_atomic_prop( string $type, $value ): array {
		return [
			'$$type' => $type,
			'value'  => $value,
		];
	}

	/**
	 * Create a link prop structure.
	 *
	 * @param string $url        The URL.
	 * @param array  $attributes HTML attributes.
	 * @return array Link prop structure.
	 */
	private function create_link_prop( string $url, array $attributes ): array {
		$target          = $attributes['target'] ?? '_self';
		$is_target_blank = ( '_blank' === $target ) ? true : null;

		return [
			'$$type' => 'link',
			'value'  => [
				'destination'   => [
					'$$type' => 'url',
					'value'  => $url,
				],
				'isTargetBlank' => $is_target_blank,
			],
		];
	}

	/**
	 * Create an image src prop structure.
	 *
	 * @param string $src Image source URL.
	 * @return array Image src prop structure.
	 */
	private function create_image_src_prop( string $src ): array {
		return [
			'$$type' => 'image-src',
			'value'  => [
				'url' => $src,
				'id'  => $this->extract_attachment_id_from_src( $src ),
			],
		];
	}

	/**
	 * Extract heading tag from attributes.
	 *
	 * @param array $attributes HTML attributes.
	 * @return string Heading tag.
	 */
	private function extract_heading_tag( array $attributes ): string {
		return $attributes['original_tag'] ?? 'h1';
	}

	/**
	 * Extract heading level from attributes.
	 *
	 * @param array $attributes HTML attributes.
	 * @return int Heading level.
	 */
	private function extract_heading_level( array $attributes ): int {
		$tag = $this->extract_heading_tag( $attributes );
		return $this->widget_mapper->get_heading_level_from_tag( $tag );
	}

	/**
	 * Filter attributes to exclude standard ones.
	 *
	 * @param array $attributes HTML attributes.
	 * @return array Filtered attributes.
	 */
	private function filter_attributes( array $attributes ): array {
		$filtered            = [];
		$excluded_attributes = [ 'style', 'class', 'id', 'href', 'src', 'alt', 'width', 'height', 'original_tag' ];

		foreach ( $attributes as $name => $value ) {
			if ( ! in_array( $name, $excluded_attributes, true ) ) {
				$filtered[ $name ] = $value;
			}
		}

		return $filtered;
	}

	/**
	 * Extract attachment ID from image source URL.
	 *
	 * @param string $src Image source URL.
	 * @return int|null Attachment ID or null.
	 */
	private function extract_attachment_id_from_src( string $src ): ?int {
		if ( empty( $src ) ) {
			return null;
		}

		if ( function_exists( 'attachment_url_to_postid' ) ) {
			$attachment_id = attachment_url_to_postid( $src );
			return $attachment_id > 0 ? $attachment_id : null;
		}

		return null;
	}
}
