<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Border_Style_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'border-style',
		'border-top-style',
		'border-right-style',
		'border-bottom-style',
		'border-left-style',
		'border-block-start-style',
		'border-block-end-style',
		'border-inline-start-style',
		'border-inline-end-style',
	];

	/**
	 * Maps physical border-style properties to logical properties.
	 */
	private const PHYSICAL_TO_LOGICAL_MAPPING = [
		'border-top-style'    => 'border-block-start-style',
		'border-bottom-style' => 'border-block-end-style',
		'border-left-style'   => 'border-inline-start-style',
		'border-right-style'  => 'border-inline-end-style',
	];

	private const VALID_STYLES = [
		'none',
		'hidden',
		'dotted',
		'dashed',
		'solid',
		'double',
		'groove',
		'ridge',
		'inset',
		'outset',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		// Map physical to logical properties
		if ( isset( self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ] ) ) {
			return self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ];
		}

		return $property;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$normalized_value = strtolower( trim( $value ) );

		if ( ! in_array( $normalized_value, self::VALID_STYLES, true ) ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized_value );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}
}
