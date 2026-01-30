<?php
/**
 * Color RGBA Variable Convertor
 *
 * Converts RGBA color variables to Elementor format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Color_Rgba_Variable_Convertor
 *
 * Handles rgba() color values with alpha channel.
 */
class Color_Rgba_Variable_Convertor extends Abstract_Variable_Convertor {

	/**
	 * Regex pattern for RGBA colors.
	 */
	private const RGBA_PATTERN = '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([\d.]+)\s*\)$/';

	/**
	 * Check if this convertor supports the value.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return bool
	 */
	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::RGBA_PATTERN, $value );
	}

	/**
	 * Get type identifier.
	 *
	 * @return string
	 */
	protected function get_type(): string {
		return 'color-rgba';
	}

	/**
	 * Normalize RGBA color value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized value.
	 */
	protected function normalize_value( string $value ): string {
		return $this->normalize_rgba( $value );
	}

	/**
	 * Normalize RGBA color.
	 *
	 * Standardizes spacing: rgba(r, g, b, a)
	 *
	 * @param string $rgba RGBA color.
	 * @return string Normalized RGBA.
	 */
	private function normalize_rgba( string $rgba ): string {
		if ( preg_match( self::RGBA_PATTERN, $rgba, $matches ) ) {
			$red   = (int) $matches[1];
			$green = (int) $matches[2];
			$blue  = (int) $matches[3];
			$alpha = (float) $matches[4];

			return "rgba({$red}, {$green}, {$blue}, {$alpha})";
		}

		return $rgba;
	}
}
