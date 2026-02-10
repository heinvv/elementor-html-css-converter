<?php

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Hsl_Variable_Convertor extends Abstract_Variable_Convertor {

	private const HSL_COMMA_PATTERN = '/^hsl\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*(\d+(?:\.\d+)?)%\s*\)$/i';
	private const HSL_SPACE_PATTERN = '/^hsl\(\s*(\d+(?:\.\d+)?)\s*(?:deg)?\s+(\d+(?:\.\d+)?)%\s+(\d+(?:\.\d+)?)%\s*\)$/i';

	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::HSL_COMMA_PATTERN, $value )
			|| 1 === preg_match( self::HSL_SPACE_PATTERN, $value );
	}

	protected function get_type(): string {
		return 'color-hsl';
	}

	protected function normalize_value( string $value ): string {
		$components = $this->extract_hsl_components( $value );

		if ( null === $components ) {
			return $value;
		}

		return self::hsl_to_hex( $components['hue'], $components['saturation'], $components['lightness'] );
	}

	private function extract_hsl_components( string $value ): ?array {
		if ( preg_match( self::HSL_COMMA_PATTERN, $value, $matches ) ) {
			return [
				'hue'        => (float) $matches[1],
				'saturation' => (float) $matches[2],
				'lightness'  => (float) $matches[3],
			];
		}

		if ( preg_match( self::HSL_SPACE_PATTERN, $value, $matches ) ) {
			return [
				'hue'        => (float) $matches[1],
				'saturation' => (float) $matches[2],
				'lightness'  => (float) $matches[3],
			];
		}

		return null;
	}

	public static function hsl_to_hex( float $hue, float $saturation, float $lightness ): string {
		list( $red, $green, $blue ) = self::hsl_to_rgb( $hue, $saturation, $lightness );

		return sprintf( '#%02x%02x%02x', $red, $green, $blue );
	}

	public static function hsl_to_rgb( float $hue, float $saturation, float $lightness ): array {
		$hue_normalized        = fmod( $hue, 360.0 ) / 360.0;
		$saturation_normalized = max( 0.0, min( 100.0, $saturation ) ) / 100.0;
		$lightness_normalized  = max( 0.0, min( 100.0, $lightness ) ) / 100.0;

		if ( 0.0 === $saturation_normalized ) {
			$channel = (int) round( $lightness_normalized * 255 );
			return [ $channel, $channel, $channel ];
		}

		$q = $lightness_normalized < 0.5
			? $lightness_normalized * ( 1.0 + $saturation_normalized )
			: $lightness_normalized + $saturation_normalized - $lightness_normalized * $saturation_normalized;

		$p = 2.0 * $lightness_normalized - $q;

		$red   = (int) round( self::hue_to_channel( $p, $q, $hue_normalized + 1.0 / 3.0 ) * 255 );
		$green = (int) round( self::hue_to_channel( $p, $q, $hue_normalized ) * 255 );
		$blue  = (int) round( self::hue_to_channel( $p, $q, $hue_normalized - 1.0 / 3.0 ) * 255 );

		return [ $red, $green, $blue ];
	}

	private static function hue_to_channel( float $p, float $q, float $t ): float {
		if ( $t < 0.0 ) {
			$t += 1.0;
		}
		if ( $t > 1.0 ) {
			$t -= 1.0;
		}

		if ( $t < 1.0 / 6.0 ) {
			return $p + ( $q - $p ) * 6.0 * $t;
		}
		if ( $t < 1.0 / 2.0 ) {
			return $q;
		}
		if ( $t < 2.0 / 3.0 ) {
			return $p + ( $q - $p ) * ( 2.0 / 3.0 - $t ) * 6.0;
		}

		return $p;
	}
}
