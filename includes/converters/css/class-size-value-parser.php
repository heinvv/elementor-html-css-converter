<?php
namespace ElementorHtmlCssConverter\Converters\Css;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Size_Value_Parser {

	private const SUPPORTED_UNITS = [
		'px',
		'em',
		'rem',
		'%',
		'vw',
		'vh',
		'vmin',
		'vmax',
		'pt',
		'cm',
		'mm',
		'in',
		'pc',
		'ex',
		'ch',
		'lh',
		'rlh',
		'svw',
		'svh',
		'lvw',
		'lvh',
		'dvw',
		'dvh',
	];

	private const PATTERN_SIZE_VALUE = '/^(-?[\d.]+)(.*)?$/';

	public static function parse( string $value ): ?array {
		$value = trim( $value );

		if ( self::is_empty( $value ) ) {
			return null;
		}

		if ( self::is_css_variable( $value ) ) {
			return [
				'size' => $value,
				'unit' => 'custom',
			];
		}

		if ( self::is_zero_without_unit( $value ) ) {
			return self::create_size_array( 0, 'px' );
		}

		if ( ! preg_match( self::PATTERN_SIZE_VALUE, $value, $matches ) ) {
			return null;
		}

		$size = self::parse_numeric_value( $matches[1] );
		$unit = self::parse_unit( $matches[2] ?? '' );

		if ( null === $size ) {
			return null;
		}

		if ( ! self::is_valid_unit( $unit ) ) {
			return null;
		}

		return self::create_size_array( $size, $unit );
	}

	private static function is_css_variable( string $value ): bool {
		return str_starts_with( $value, 'var(' );
	}

	public static function is_valid_unit( string $unit ): bool {
		return in_array( $unit, self::SUPPORTED_UNITS, true );
	}

	public static function get_supported_units(): array {
		return self::SUPPORTED_UNITS;
	}

	private static function is_empty( string $value ): bool {
		return '' === $value;
	}

	private static function is_zero_without_unit( string $value ): bool {
		return '0' === $value;
	}

	private static function parse_numeric_value( string $value ): ?float {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return (float) $value;
	}

	private static function parse_unit( string $unit ): string {
		$unit = strtolower( trim( $unit ) );

		return '' === $unit ? 'px' : $unit;
	}

	private static function create_size_array( float $size, string $unit ): array {
		return [
			'size' => $size,
			'unit' => $unit,
		];
	}
}
