<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Positioning_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'top',
		'right',
		'bottom',
		'left',
		'z-index',
		'inset-block-start',
		'inset-block-end',
		'inset-inline-start',
		'inset-inline-end',
	];

	/**
	 * Maps physical positioning properties to logical properties.
	 */
	private const PHYSICAL_TO_LOGICAL_MAPPING = [
		'top'    => 'inset-block-start',
		'bottom' => 'inset-block-end',
		'left'   => 'inset-inline-start',
		'right'  => 'inset-inline-end',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	public function get_output_property( string $property ): string {
		// Map physical to logical properties
		if ( isset( self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ] ) ) {
			return self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ];
		}

		return $property;
	}

	/**
	 * Override convert to handle z-index separately (no variable resolution for z-index).
	 */
	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$normalized_value = trim( $value );

		// Handle z-index separately (it's a number, not a size, no variable support)
		if ( 'z-index' === $property ) {
			return $this->convert_z_index( $normalized_value );
		}

		// Check for CSS variable and resolve
		if ( Variable_Resolver::is_css_variable( $normalized_value ) ) {
			return Variable_Resolver::resolve( $normalized_value, 'size' );
		}

		// Handle 'auto' keyword
		if ( 'auto' === strtolower( $normalized_value ) ) {
			return Size_Prop_Type::generate( [
				'size' => 'auto',
				'unit' => 'custom',
			] );
		}

		$size_value = Size_Value_Parser::parse( $normalized_value );

		if ( null === $size_value ) {
			return null;
		}

		return Size_Prop_Type::generate( $size_value );
	}

	protected function convert_value( string $property, $value ): ?array {
		// Not used directly - convert() handles everything
		return null;
	}

	private function convert_z_index( string $value ): ?array {
		// Handle 'auto' keyword
		if ( 'auto' === strtolower( $value ) ) {
			return Number_Prop_Type::generate( 0 );
		}

		// Must be an integer
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$int_value = (int) $value;

		return Number_Prop_Type::generate( $int_value );
	}
}
