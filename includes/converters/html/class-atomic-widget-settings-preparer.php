<?php
/**
 * Atomic Widget Settings Preparer
 *
 * Prepares widget settings based on widget type and element data.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

use ElementorHtmlCssConverter\Converters\Images\Image_Import_Service;
use ElementorHtmlCssConverter\Converters\Images\Image_Url_Helper;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

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
	 * Image import service instance.
	 *
	 * @var Image_Import_Service|null
	 */
	private ?Image_Import_Service $image_import_service = null;

	/**
	 * Whether to import images during settings preparation.
	 *
	 * @var bool
	 */
	private bool $import_images = true;

	/**
	 * Warnings collected during settings preparation.
	 *
	 * @var array
	 */
	private array $warnings = [];

	/**
	 * Constructor.
	 *
	 * @param bool $import_images Whether to import external images during preparation.
	 */
	public function __construct( bool $import_images = true ) {
		$this->widget_mapper = new HTML_To_Atomic_Widget_Mapper();
		$this->import_images = $import_images;

		if ( $import_images ) {
			$this->image_import_service = new Image_Import_Service();
		}
	}

	/**
	 * Get collected warnings.
	 *
	 * @return array Array of warning messages.
	 */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/**
	 * Clear collected warnings.
	 *
	 * @return void
	 */
	public function clear_warnings(): void {
		$this->warnings = [];
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

		$settings = $this->add_default_settings( $settings, $widget_type );

		$settings['classes'] = [
			'$$type' => 'classes',
			'value'  => [],
		];

		if ( ! empty( $attributes ) ) {
			$filtered_attributes = $this->filter_attributes( $attributes, $widget_type );
			if ( ! empty( $filtered_attributes ) ) {
				$settings['attributes'] = $this->create_attributes_prop( $filtered_attributes );
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
				$settings['title'] = $this->create_html_v2_prop( $content );
				$settings['tag']   = $this->create_atomic_prop( 'string', $this->extract_heading_tag( $attributes ) );
				$settings['level'] = $this->extract_heading_level( $attributes );
				break;

			case 'e-paragraph':
				$settings['paragraph'] = $this->create_html_v2_prop( $content );
				$settings['tag'] = $this->create_atomic_prop( 'string', $this->extract_paragraph_tag( $attributes ) );
				break;

			case 'e-button':
				$settings['text'] = $this->create_html_v2_prop( $content );
				if ( isset( $attributes['href'] ) ) {
					$settings['link'] = $this->create_link_prop( $attributes['href'], $attributes );
				}
				break;

			case 'e-image':
				$image_src_prop = $this->create_image_src_prop( $attributes['src'] ?? '' );
				$settings['image'] = Image_Prop_Type::generate( [
					'src'  => $image_src_prop,
					'size' => String_Prop_Type::generate( 'full' ),
				] );
				$settings['alt'] = $this->create_atomic_prop( 'string', $attributes['alt'] ?? '' );
				break;

			case 'e-svg':
				$svg_content = $attributes['svg_content'] ?? null;
				if ( ! empty( $svg_content ) && $this->import_images && $this->image_import_service ) {
					$import_result = $this->image_import_service->import_inline_svg( $svg_content );
					if ( $import_result['success'] && isset( $import_result['id'] ) ) {
						$settings['svg'] = Image_Src_Prop_Type::generate( [
							'id'  => Image_Attachment_Id_Prop_Type::generate( $import_result['id'] ),
							'url' => null,
						] );
					} else {
						if ( ! empty( $import_result['warnings'] ) ) {
							$this->warnings = array_merge( $this->warnings, $import_result['warnings'] );
						}
					}
				}
				if ( isset( $attributes['href'] ) ) {
					$settings['link'] = $this->create_link_prop( $attributes['href'], $attributes );
				}
				break;

			case 'e-flexbox':
				break;

			case 'e-div-block':
				if ( isset( $attributes['is_link_container'] ) && $attributes['is_link_container'] ) {
					$settings['tag'] = $this->create_atomic_prop( 'string', 'a' );
					if ( isset( $attributes['href'] ) ) {
						$settings['link'] = $this->create_link_prop( $attributes['href'], $attributes );
					}
				}
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
	 * Create an html-v3 prop structure for Html_V3_Prop_Type.
	 *
	 * Elementor expects { content: { $$type: 'string', value: string }, children: [] }
	 * for e-paragraph, e-heading, e-button.
	 *
	 * @param string $content Text or HTML content.
	 * @return array Atomic prop structure.
	 */
	private function create_html_v2_prop( string $content ): array {
		$allowed_tags = $this->get_html_v2_allowed_tags();
		$sanitized    = \wp_kses( $content, $allowed_tags );
		$normalized   = $this->normalize_deprecated_inline_tags( $sanitized );

		return [
			'$$type' => 'html-v3',
			'value'  => [
				'content'  => [
					'$$type' => 'string',
					'value'  => $normalized,
				],
				'children' => [],
			],
		];
	}

	private function normalize_deprecated_inline_tags( string $html ): string {
		$html = preg_replace( '/<\/b\b>/i', '</strong>', $html );
		$html = preg_replace( '/<b(\s|>)/i', '<strong$1', $html );
		$html = preg_replace( '/<\/i\b>/i', '</em>', $html );
		$html = preg_replace( '/<i(\s|>)/i', '<em$1', $html );

		return $html;
	}

	/**
	 * Get allowed tags for html-v2 content sanitization.
	 *
	 * @return array<string, array> Tag name to allowed attributes map.
	 */
	private function get_html_v2_allowed_tags(): array {
		if ( class_exists( '\Elementor\Modules\AtomicWidgets\PropTypes\Html_Prop_Type' )
			&& method_exists( '\Elementor\Modules\AtomicWidgets\PropTypes\Html_Prop_Type', 'get_base_allowed_tags' ) ) {
			return \Elementor\Modules\AtomicWidgets\PropTypes\Html_Prop_Type::get_base_allowed_tags();
		}

		return [
			'b'      => [],
			'i'      => [],
			'em'     => [],
			'u'      => [],
			'a'      => [ 'href' => true, 'target' => true ],
			'del'    => [],
			'span'   => [],
			'br'     => [],
			'strong' => [],
			'sup'    => [],
			'sub'    => [],
			's'      => [],
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
		$is_target_blank = ( '_blank' === $target );

		return [
			'$$type' => 'link',
			'value'  => [
				'destination'   => [
					'$$type' => 'url',
					'value'  => $url,
				],
				'isTargetBlank' => $is_target_blank ? Boolean_Prop_Type::generate( true ) : null,
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
		if ( empty( $src ) ) {
			return [
				'$$type' => 'image-src',
				'value'  => [
					'url' => '',
					'id'  => null,
				],
			];
		}

		$resolved_src = Image_Url_Helper::resolve_image_url( $src );

		$local_id = $this->extract_attachment_id_from_src( $resolved_src );

		if ( $local_id ) {
			return [
				'$$type' => 'image-src',
				'value'  => [
					'id'  => Image_Attachment_Id_Prop_Type::generate( $local_id ),
					'url' => null,
				],
			];
		}

		if ( $this->import_images && $this->image_import_service && Image_Url_Helper::is_external_url( $resolved_src ) ) {
			$imported = $this->image_import_service->import_image_url( $resolved_src );
			if ( $imported && isset( $imported['id'] ) ) {
				return [
					'$$type' => 'image-src',
					'value'  => [
						'id'  => Image_Attachment_Id_Prop_Type::generate( $imported['id'] ),
						'url' => null,
					],
				];
			}
		}
		return [
			'$$type' => 'image-src',
			'value'  => [
				'url' => $resolved_src,
				'id'  => null,
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

	private function extract_paragraph_tag( array $attributes ): string {
		$tag = $attributes['original_tag'] ?? 'p';
		return in_array( $tag, [ 'p', 'span' ], true ) ? $tag : 'p';
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
	 * @param array  $attributes  HTML attributes.
	 * @param string $widget_type Widget type.
	 * @return array Filtered attributes.
	 */
	private function filter_attributes( array $attributes, string $widget_type = '' ): array {
		$filtered            = [];
		$excluded_attributes = [ 'style', 'class', 'id', 'href', 'src', 'alt', 'original_tag', 'svg_content', 'is_link_container' ];

		if ( 'e-svg' === $widget_type ) {
			$excluded_attributes = array_merge( $excluded_attributes, [ 'width', 'height', 'fill', 'viewbox', 'viewBox', 'xmlns' ] );
		} else {
			$excluded_attributes[] = 'width';
			$excluded_attributes[] = 'height';
		}

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

	/**
	 * Create attributes prop structure.
	 *
	 * @param array $attributes HTML attributes.
	 * @return array Attributes prop structure.
	 */
	private function create_attributes_prop( array $attributes ): array {
		$key_value_items = [];

		foreach ( $attributes as $name => $value ) {
			$key_value_items[] = Key_Value_Prop_Type::generate( [
				'key'   => String_Prop_Type::generate( $name ),
				'value' => String_Prop_Type::generate( $value ),
			] );
		}

		return Attributes_Prop_Type::generate( $key_value_items );
	}
}
