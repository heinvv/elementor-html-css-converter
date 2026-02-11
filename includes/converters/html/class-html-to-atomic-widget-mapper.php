<?php
/**
 * HTML to Atomic Widget Mapper
 *
 * Maps HTML tags to Elementor atomic widget types.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HTML_To_Atomic_Widget_Mapper
 *
 * Maps HTML element tags to their corresponding Elementor atomic widget types.
 */
class HTML_To_Atomic_Widget_Mapper {

	/**
	 * Mapping of HTML tags to atomic widget configurations.
	 *
	 * @var array
	 */
	private array $widget_mapping = [
		'h1'         => [
			'type'  => 'e-heading',
			'level' => 1,
		],
		'h2'         => [
			'type'  => 'e-heading',
			'level' => 2,
		],
		'h3'         => [
			'type'  => 'e-heading',
			'level' => 3,
		],
		'h4'         => [
			'type'  => 'e-heading',
			'level' => 4,
		],
		'h5'         => [
			'type'  => 'e-heading',
			'level' => 5,
		],
		'h6'         => [
			'type'  => 'e-heading',
			'level' => 6,
		],
		'p'          => [ 'type' => 'e-paragraph' ],
		'blockquote' => [ 'type' => 'e-paragraph' ],
		'button'     => [ 'type' => 'e-button' ],
		'a'          => [ 'type' => 'e-button' ],
		'img'        => [ 'type' => 'e-image' ],
		'svg'        => [ 'type' => 'e-svg' ],
		'div'        => [ 'type' => 'e-div-block' ],
		'section'    => [ 'type' => 'e-div-block' ],
		'article'    => [ 'type' => 'e-div-block' ],
		'header'     => [ 'type' => 'e-div-block' ],
		'footer'     => [ 'type' => 'e-div-block' ],
		'main'       => [ 'type' => 'e-div-block' ],
		'aside'      => [ 'type' => 'e-div-block' ],
		'span'       => [ 'type' => 'e-div-block' ],
		'nav'        => [ 'type' => 'e-div-block' ],
		'ul'         => [ 'type' => 'e-div-block' ],
		'ol'         => [ 'type' => 'e-div-block' ],
		'li'         => [ 'type' => 'e-div-block' ],
	];

	/**
	 * Get widget configuration for an HTML tag.
	 *
	 * @param string $html_tag The HTML tag name.
	 * @return array|null Widget configuration or null if not supported.
	 */
	public function get_widget_config( string $html_tag ): ?array {
		return $this->widget_mapping[ $html_tag ] ?? null;
	}

	/**
	 * Check if a widget type is a container.
	 *
	 * @param string $widget_type The widget type.
	 * @return bool True if container widget.
	 */
	public function is_container_widget( string $widget_type ): bool {
		return 'e-div-block' === $widget_type || 'e-flexbox' === $widget_type;
	}

	/**
	 * Check if an HTML tag is supported.
	 *
	 * @param string $html_tag The HTML tag name.
	 * @return bool True if supported.
	 */
	public function is_supported_tag( string $html_tag ): bool {
		return isset( $this->widget_mapping[ $html_tag ] );
	}

	/**
	 * Get all supported HTML tags.
	 *
	 * @return array List of supported tags.
	 */
	public function get_supported_tags(): array {
		return array_keys( $this->widget_mapping );
	}

	/**
	 * Get all unique widget types.
	 *
	 * @return array List of widget types.
	 */
	public function get_widget_types(): array {
		$widget_types = [];

		foreach ( $this->widget_mapping as $config ) {
			$widget_types[] = $config['type'];
		}

		return array_unique( $widget_types );
	}

	/**
	 * Get all HTML tags that map to a specific widget type.
	 *
	 * @param string $widget_type The widget type.
	 * @return array List of HTML tags.
	 */
	public function get_tags_for_widget_type( string $widget_type ): array {
		$tags = [];

		foreach ( $this->widget_mapping as $tag => $config ) {
			if ( $config['type'] === $widget_type ) {
				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Get heading level from tag.
	 *
	 * @param string $tag The heading tag (h1-h6).
	 * @return int The heading level (1-6).
	 */
	public function get_heading_level_from_tag( string $tag ): int {
		$config = $this->get_widget_config( $tag );
		return $config['level'] ?? 1;
	}

	/**
	 * Check if tag should extract href attribute.
	 *
	 * @param string $tag The HTML tag.
	 * @return bool True if href should be extracted.
	 */
	public function should_extract_href( string $tag ): bool {
		return 'a' === $tag;
	}

	/**
	 * Check if tag should extract src attribute.
	 *
	 * @param string $tag The HTML tag.
	 * @return bool True if src should be extracted.
	 */
	public function should_extract_src( string $tag ): bool {
		return 'img' === $tag;
	}

	/**
	 * Get default flexbox settings.
	 *
	 * @return array Default flexbox configuration.
	 */
	public function get_default_flexbox_settings(): array {
		return [
			'direction'       => 'column',
			'wrap'            => 'nowrap',
			'justify_content' => 'flex-start',
			'align_items'     => 'stretch',
			'gap'             => [
				'column' => '0',
				'row'    => '0',
			],
		];
	}
}
