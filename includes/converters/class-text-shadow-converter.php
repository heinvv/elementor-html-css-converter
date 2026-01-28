<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Text Shadow Converter
 *
 * Note: Elementor's atomic widgets do NOT support text-shadow.
 * The Style_Schema does not include text-shadow as a supported property.
 * This converter always returns null to skip text-shadow properties.
 * Text-shadow will fall back to custom CSS if needed.
 */
class Text_Shadow_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'text-shadow' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	/**
	 * Convert text-shadow property.
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return null Always returns null as text-shadow is not supported in Elementor atomic widgets.
	 */
	public function convert( string $property, $value ): ?array {
		// Elementor atomic widgets do not support text-shadow.
		// Return null to skip this property (it will be handled as custom CSS).
		return null;
	}
}
