<?php
/**
 * Color Prop Type Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\PropTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Color_Prop_Type
 *
 * Minimal replication of Elementor's prop-type generate() method
 * to create the atomic widget format for color values.
 */
class Color_Prop_Type {
	/**
	 * The prop type key.
	 */
	private const KEY = 'color';

	/**
	 * Get the prop type key.
	 *
	 * @return string The key identifying this prop type.
	 */
	public static function get_key(): string {
		return self::KEY;
	}

	/**
	 * Generate the atomic widget format for a color value.
	 *
	 * Matches Elementor's Has_Generate trait output format exactly.
	 *
	 * @param mixed $value   The color value.
	 * @param bool  $disable Whether to mark as disabled.
	 * @return array The atomic format array.
	 */
	public static function generate( $value, bool $disable = false ): array {
		$result = [
			'$$type' => static::get_key(),
			'value'  => $value,
		];

		if ( $disable ) {
			$result['disabled'] = true;
		}

		return $result;
	}
}
