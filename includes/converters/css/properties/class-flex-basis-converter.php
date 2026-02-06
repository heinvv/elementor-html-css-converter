<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Flex_Basis_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex-basis' ];

	private const KEYWORD_VALUES = [
		'auto'        => [ 'size' => 'auto', 'unit' => 'custom' ],
		'content'     => [ 'size' => 'content', 'unit' => 'custom' ],
		'fit-content' => [ 'size' => 'fit-content', 'unit' => 'custom' ],
		'max-content' => [ 'size' => 'max-content', 'unit' => 'custom' ],
		'min-content' => [ 'size' => 'min-content', 'unit' => 'custom' ],
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	public function get_output_property( string $property ): string {
		return 'flex';
	}

	protected function wrap_resolved_variable( array $resolved, string $property ): array {
		return Flex_Prop_Type::generate( [
			'flexBasis' => $resolved,
		] );
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = strtolower( trim( $value ) );

		if ( isset( self::KEYWORD_VALUES[ $value ] ) ) {
			return Flex_Prop_Type::generate( [
				'flexBasis' => Size_Prop_Type::generate( self::KEYWORD_VALUES[ $value ] ),
			] );
		}

		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Flex_Prop_Type::generate( [
			'flexBasis' => Size_Prop_Type::generate( $parsed ),
		] );
	}
}

