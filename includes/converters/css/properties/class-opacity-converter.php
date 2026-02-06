<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Opacity_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'opacity' ];
	private const PERCENTAGE_CONVERSION_FACTOR = 100;
	private const OPACITY_MIN = 0;
	private const OPACITY_MAX_DECIMAL = 1;
	private const OPACITY_MAX_PERCENTAGE = 100;

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$opacity_data = $this->parse_opacity_value( $value );

		if ( null === $opacity_data ) {
			return null;
		}

		return Size_Prop_Type::generate( [
			'size' => $opacity_data['size'] * self::PERCENTAGE_CONVERSION_FACTOR,
			'unit' => '%',
		] );
	}

	private function parse_opacity_value( string $value ): ?array {
		$value = trim( $value );

		if ( $this->is_percentage_value( $value ) ) {
			return $this->parse_percentage_value( $value );
		}

		return $this->parse_decimal_value( $value );
	}

	private function is_percentage_value( string $value ): bool {
		return str_ends_with( $value, '%' );
	}

	private function parse_percentage_value( string $value ): ?array {
		$numeric_value = (float) rtrim( $value, '%' );

		if ( $numeric_value < self::OPACITY_MIN || $numeric_value > self::OPACITY_MAX_PERCENTAGE ) {
			return null;
		}

		return [
			'size' => $numeric_value / self::PERCENTAGE_CONVERSION_FACTOR,
			'unit' => '%',
		];
	}

	private function parse_decimal_value( string $value ): ?array {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$numeric_value = (float) $value;

		if ( $numeric_value < self::OPACITY_MIN || $numeric_value > self::OPACITY_MAX_DECIMAL ) {
			return null;
		}

		return [
			'size' => $numeric_value,
			'unit' => '%',
		];
	}
}

