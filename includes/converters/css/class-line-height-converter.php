<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Line_Height_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'line-height' ];
	private const NORMAL_LINE_HEIGHT_VALUE = 1.2;

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	protected function convert_value( string $property, $value ): ?array {
		$line_height_data = $this->parse_line_height_value( $value );

		if ( null === $line_height_data ) {
			return null;
		}

		return Size_Prop_Type::generate( $line_height_data );
	}

	private function parse_line_height_value( string $value ): ?array {
		$value = trim( $value );

		if ( $this->is_normal_keyword( $value ) ) {
			return $this->create_normal_line_height();
		}

		if ( $this->is_unitless_numeric( $value ) ) {
			return $this->create_unitless_line_height( (float) $value );
		}

		return Size_Value_Parser::parse( $value );
	}

	private function is_normal_keyword( string $value ): bool {
		return 'normal' === strtolower( $value );
	}

	private function is_unitless_numeric( string $value ): bool {
		return is_numeric( $value );
	}

	private function create_unitless_line_height( float $value ): array {
		return [
			'size' => $value,
			'unit' => 'em',
		];
	}

	private function create_normal_line_height(): array {
		return [
			'size' => self::NORMAL_LINE_HEIGHT_VALUE,
			'unit' => 'em',
		];
	}
}
