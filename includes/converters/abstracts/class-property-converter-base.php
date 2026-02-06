<?php
/**
 * Property Converter Base Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Abstracts;

use ElementorHtmlCssConverter\Converters\Interfaces\Property_Converter_Interface;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	 * Handles CSS variable resolution automatically.
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return array|null The atomic format array or null if conversion fails.
	 */
	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		if ( Variable_Resolver::is_css_variable( $value ) ) {
			$variable_type = $this->get_variable_type();

			if ( null === $variable_type ) {
				return null;
			}

			$resolved = Variable_Resolver::resolve( $value, $variable_type );

			if ( null === $resolved ) {
				return null;
			}

			return $this->wrap_resolved_variable( $resolved, $property );
		}

		return $this->convert_value( $property, $value );
	}

	/**
	 * Convert the actual value (after variable resolution check).
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return array|null The atomic format array or null if conversion fails.
	 */
	abstract protected function convert_value( string $property, $value ): ?array;

	/**
	 * Get the variable type for this converter ('color', 'size', or null if variables not supported).
	 * Override in subclasses to enable variable resolution.
	 *
	 * @return string|null The variable type or null.
	 */
	protected function get_variable_type(): ?string {
		return null;
	}

	/**
	 * Wrap a resolved variable in the appropriate prop structure if needed.
	 * Override in subclasses that need special wrapping (e.g., background).
	 *
	 * @param array  $resolved The resolved variable data.
	 * @param string $property The CSS property name.
	 * @return array The wrapped variable data.
	 */
	protected function wrap_resolved_variable( array $resolved, string $property ): array {
		return $resolved;
	}

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

