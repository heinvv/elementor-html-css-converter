<?php
/**
 * Property Converter Interface
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Interface Property_Converter_Interface
 *
 * Defines the contract for property converters that convert CSS properties
 * to Elementor atomic widget format.
 */
interface Property_Converter_Interface {
	/**
	 * Check if this converter supports a given property.
	 *
	 * @param string     $property The CSS property name.
	 * @param mixed|null $value    Optional value to check support for.
	 * @return bool True if the property is supported.
	 */
	public function supports( string $property, $value = null ): bool;

	/**
	 * Convert a CSS property and value to atomic format.
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return array|null The atomic format array or null if conversion fails.
	 */
	public function convert( string $property, $value ): ?array;

	/**
	 * Get the list of supported CSS properties.
	 *
	 * @return array List of supported property names.
	 */
	public function get_supported_properties(): array;

	/**
	 * Get the output property name for a given input property.
	 *
	 * @param string $property The input CSS property name.
	 * @return string The output Elementor property name.
	 */
	public function get_output_property( string $property ): string;
}
