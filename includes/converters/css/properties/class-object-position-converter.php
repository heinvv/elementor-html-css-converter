<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Object_Position_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'object-position' ];

	private const KEYWORD_TO_ENUM = [
		'center'        => 'center center',
		'top'           => 'top center',
		'bottom'        => 'bottom center',
		'left'          => 'center left',
		'right'         => 'center right',
		'top left'      => 'top left',
		'top right'     => 'top right',
		'bottom left'   => 'bottom left',
		'bottom right'  => 'bottom right',
		'center center' => 'center center',
		'center left'   => 'center left',
		'center right'  => 'center right',
		'top center'    => 'top center',
		'bottom center' => 'bottom center',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized = preg_replace( '/\s+/', ' ', strtolower( trim( $value ) ) );

		$mapped = self::KEYWORD_TO_ENUM[ $normalized ] ?? null;

		if ( null === $mapped ) {
			return null;
		}

		return String_Prop_Type::generate( $mapped );
	}
}
