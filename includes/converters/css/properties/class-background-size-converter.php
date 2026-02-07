<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Image_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Image_Overlay_Size_Scale_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Size_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'background-size' ];
	private const OUTPUT_PROPERTY = 'background';

	private const KEYWORD_VALUES = [
		'auto'     => 'auto',
		'cover'     => 'cover',
		'contain'  => 'contain',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return self::OUTPUT_PROPERTY;
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( empty( $value ) || 'none' === strtolower( $value ) ) {
			return null;
		}

		$size_value = $this->parse_size_value( $value );

		if ( null === $size_value ) {
			return null;
		}

		return Background_Prop_Type::generate( [
			'background-overlay' => Background_Overlay_Prop_Type::generate( [
				Background_Image_Overlay_Prop_Type::generate( [
					'image' => Image_Prop_Type::generate( [
						'src' => [
							'$$type' => 'image-src',
							'value'  => [
								'id'  => null,
								'url' => 'none',
							],
						],
					] ),
					'size' => $size_value,
				] ),
			] ),
		] );
	}

	private function parse_size_value( string $value ): ?array {
		$value_lower = strtolower( trim( $value ) );

		if ( isset( self::KEYWORD_VALUES[ $value_lower ] ) ) {
			return String_Prop_Type::generate( self::KEYWORD_VALUES[ $value_lower ] );
		}

		if ( 'auto auto' === $value_lower ) {
			return String_Prop_Type::generate( 'auto' );
		}

		$parts = preg_split( '/\s+/', trim( $value ) );
		$count = count( $parts );

		if ( 2 === $count ) {
			$width_parsed = Size_Value_Parser::parse( $parts[0] );
			$height_parsed = Size_Value_Parser::parse( $parts[1] );

			if ( null !== $width_parsed && null !== $height_parsed ) {
				return Background_Image_Overlay_Size_Scale_Prop_Type::generate( [
					'width' => Size_Prop_Type::generate( $width_parsed ),
					'height' => Size_Prop_Type::generate( $height_parsed ),
				] );
			}
		}

		if ( 1 === $count ) {
			$parsed = Size_Value_Parser::parse( $parts[0] );
			if ( null !== $parsed ) {
				return Background_Image_Overlay_Size_Scale_Prop_Type::generate( [
					'width' => Size_Prop_Type::generate( $parsed ),
					'height' => Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'custom' ] ),
				] );
			}
		}

		return String_Prop_Type::generate( $value );
	}
}
