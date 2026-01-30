<?php
/**
 * Color RGB Variable Convertor
 *
 * Converts RGB color variables to Elementor format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Convertors\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Color_Rgb_Variable_Convertor
 *
 * Handles rgb() color values.
 */
class Color_Rgb_Variable_Convertor extends Abstract_Variable_Convertor {

	/**
	 * Regex pattern for RGB colors.
	 */
	private const RGB_PATTERN = '/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/';

	/**
	 * Check if this convertor supports the value.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return bool
	 */
	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::RGB_PATTERN, $value );
	}

	/**
	 * Get type identifier.
	 *
	 * @return string
	 */
	protected function get_type(): string {
		return 'color-rgb';
	}

	/**
	 * Normalize RGB color value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized value.
	 */
	protected function normalize_value( string $value ): string {
		return $this->normalize_rgb( $value );
	}

	/**
	 * Normalize RGB color.
	 *
	 * Standardizes spacing: rgb(r, g, b)
	 *
	 * @param string $rgb RGB color.
	 * @return string Normalized RGB.
	 */
	private function normalize_rgb( string $rgb ): string {
		if ( preg_match( self::RGB_PATTERN, $rgb, $matches ) ) {
			$red   = (int) $matches[1];
			$green = (int) $matches[2];
			$blue  = (int) $matches[3];

			return "rgb({$red}, {$green}, {$blue})";
		}

		return $rgb;
	}
}
