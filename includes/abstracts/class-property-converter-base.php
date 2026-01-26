<?php
/**
 * Property Converter Base Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Abstracts;

use ElementorHtmlCssConverter\Interfaces\Property_Converter_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract Class Property_Converter_Base
 *
 * Base implementation for property converters.
 */
abstract class Property_Converter_Base implements Property_Converter_Interface {
	/**
	 * Get the list of supported properties for this converter.
	 *
	 * @return array List of supported property names.
	 */
	abstract protected function get_supported_properties_list(): array;

	/**
	 * Check if this converter supports a given property.
	 *
	 * @param string     $property The CSS property name.
	 * @param mixed|null $value    Optional value to check support for.
	 * @return bool True if the property is supported.
	 */
	public function supports( string $property, $value = null ): bool {
		return in_array( $property, $this->get_supported_properties(), true );
	}

	/**
	 * Get the list of supported CSS properties.
	 *
	 * @return array List of supported property names.
	 */
	public function get_supported_properties(): array {
		return $this->get_supported_properties_list();
	}

	/**
	 * Convert a CSS property and value to atomic format.
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return array|null The atomic format array or null if conversion fails.
	 */
	abstract public function convert( string $property, $value ): ?array;

	/**
	 * Get the output property name for a given input property.
	 *
	 * Default implementation returns the same property name.
	 * Override in subclasses for property name mapping.
	 *
	 * @param string $property The input CSS property name.
	 * @return string The output Elementor property name.
	 */
	public function get_output_property( string $property ): string {
		return $property;
	}
}
