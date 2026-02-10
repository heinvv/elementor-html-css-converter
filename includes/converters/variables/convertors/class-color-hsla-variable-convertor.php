<?php

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Hsla_Variable_Convertor extends Abstract_Variable_Convertor {

	private const HSLA_COMMA_PATTERN = '/^hsla\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*([\d.]+)\s*\)$/i';
	private const HSL_SLASH_PATTERN = '/^hsl\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s+(\d+(?:\.\d+)?)%\s+(\d+(?:\.\d+)?)%\s*\/\s*([\d.]+)\s*\)$/i';

	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::HSLA_COMMA_PATTERN, $value )
			|| 1 === preg_match( self::HSL_SLASH_PATTERN, $value );
	}

	protected function get_type(): string {
		return 'color-hsla';
	}

	protected function normalize_value( string $value ): string {
		$components = $this->extract_hsla_components( $value );

		if ( null === $components ) {
			return $value;
		}

		list( $red, $green, $blue ) = Color_Hsl_Variable_Convertor::hsl_to_rgb(
			$components['hue'],
			$components['saturation'],
			$components['lightness']
		);

		$alpha = $components['alpha'];

		return "rgba({$red}, {$green}, {$blue}, {$alpha})";
	}

	private function extract_hsla_components( string $value ): ?array {
		if ( preg_match( self::HSLA_COMMA_PATTERN, $value, $matches ) ) {
			return [
				'hue'        => (float) $matches[1],
				'saturation' => (float) $matches[2],
				'lightness'  => (float) $matches[3],
				'alpha'      => (float) $matches[4],
			];
		}

		if ( preg_match( self::HSL_SLASH_PATTERN, $value, $matches ) ) {
			return [
				'hue'        => (float) $matches[1],
				'saturation' => (float) $matches[2],
				'lightness'  => (float) $matches[3],
				'alpha'      => (float) $matches[4],
			];
		}

		return null;
	}
}
