<?php
/**
 * Converter Registry Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Interfaces\Property_Converter_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Converter_Registry
 *
 * Registry for managing property converters using a registry pattern.
 * Maps CSS property names to their respective converters.
 */
class Converter_Registry {
	/**
	 * Registered converters mapped by property name.
	 *
	 * @var array<string, Property_Converter_Interface>
	 */
	private array $converters = [];

	/**
	 * Register a converter for all its supported properties.
	 *
	 * @param Property_Converter_Interface $converter The converter to register.
	 * @return void
	 */
	public function register( Property_Converter_Interface $converter ): void {
		foreach ( $converter->get_supported_properties() as $property ) {
			$this->converters[ $property ] = $converter;
		}
	}

	/**
	 * Resolve a converter for a given property.
	 *
	 * @param string $property The CSS property name.
	 * @return Property_Converter_Interface|null The converter or null if not found.
	 */
	public function resolve( string $property ): ?Property_Converter_Interface {
		return $this->converters[ $property ] ?? null;
	}
}
