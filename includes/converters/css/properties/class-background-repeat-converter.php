<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Image_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Repeat_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'background-repeat' ];
	private const OUTPUT_PROPERTY = 'background';

	private const VALID_VALUES = [
		'repeat',
		'repeat-x',
		'repeat-y',
		'no-repeat',
		'space',
		'round',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return self::OUTPUT_PROPERTY;
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( empty( $value ) ) {
			return null;
		}

		$value_lower = strtolower( $value );

		if ( ! in_array( $value_lower, self::VALID_VALUES, true ) ) {
			return null;
		}

		$repeat_value = String_Prop_Type::generate( $value_lower );

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
					'repeat' => $repeat_value,
				] ),
			] ),
		] );
	}
}
