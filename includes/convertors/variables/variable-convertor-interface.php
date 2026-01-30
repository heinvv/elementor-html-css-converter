<?php
/**
 * Variable Convertor Interface
 *
 * Interface for CSS variable convertors that transform CSS variable declarations
 * into Elementor's global variables format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Convertors\Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Variable_Convertor_Interface
 */
interface Variable_Convertor_Interface {
	/**
	 * Check if this convertor supports the given variable.
	 *
	 * @param string $name  Variable name (e.g., '--primary-color').
	 * @param string $value Variable value (e.g., '#ff0000').
	 * @return bool True if this convertor can handle this variable.
	 */
	public function supports( string $name, string $value ): bool;

	/**
	 * Convert the variable to Elementor format.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return array Converted variable data.
	 */
	public function convert( string $name, string $value ): array;
}
