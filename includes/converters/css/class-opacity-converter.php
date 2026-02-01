<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Opacity_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'opacity' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		$opacity_data = $this->parse_opacity_value( $value );

		if ( null === $opacity_data ) {
			return null;
		}

		return Size_Prop_Type::generate( [
			'size' => $opacity_data['size'] * 100,
			'unit' => '%',
		] );
	}

	private function parse_opacity_value( $value ): ?array {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return null;
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return null;
		}

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

		if ( $numeric_value < 0 || $numeric_value > 100 ) {
			return null;
		}

		return [
			'size' => $numeric_value / 100,
			'unit' => '%',
		];
	}

	private function parse_decimal_value( string $value ): ?array {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$numeric_value = (float) $value;

		if ( $numeric_value < 0 || $numeric_value > 1 ) {
			return null;
		}

		return [
			'size' => $numeric_value,
			'unit' => '%',
		];
	}
}
