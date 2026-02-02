<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flex Basis Converter
 *
 * Converts flex-basis to a Flex_Prop_Type with only flexBasis set.
 * This allows merging with other flex properties.
 */
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

	/**
	 * Output property is 'flex' to allow merging with flex shorthand.
	 */
	public function get_output_property( string $property ): string {
		return 'flex';
	}

	/**
	 * Wrap resolved variable in Flex_Prop_Type structure.
	 */
	protected function wrap_resolved_variable( array $resolved, string $property ): array {
		return Flex_Prop_Type::generate( [
			'flexBasis' => $resolved,
		] );
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = strtolower( trim( $value ) );

		// Check for keyword values
		if ( isset( self::KEYWORD_VALUES[ $value ] ) ) {
			return Flex_Prop_Type::generate( [
				'flexBasis' => Size_Prop_Type::generate( self::KEYWORD_VALUES[ $value ] ),
			] );
		}

		// Try to parse as size value
		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		// Return a Flex_Prop_Type with only flexBasis set
		return Flex_Prop_Type::generate( [
			'flexBasis' => Size_Prop_Type::generate( $parsed ),
		] );
	}
}
