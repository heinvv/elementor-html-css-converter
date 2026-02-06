<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Text_Shadow_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'text-shadow' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		return null;
	}
}

