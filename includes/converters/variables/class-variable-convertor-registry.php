<?php
/**
 * Variable Convertor Registry
 *
 * Registry that manages all variable convertors and finds the appropriate
 * convertor for each variable based on its value.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Hex_Variable_Convertor;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Named_Variable_Convertor;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Rgb_Variable_Convertor;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Rgba_Variable_Convertor;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Length_Size_Viewport_Variable_Convertor;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Percentage_Variable_Convertor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variable_Convertor_Registry
 *
 * Manages a collection of variable convertors.
 */
class Variable_Convertor_Registry {

	/**
	 * Array of registered convertors.
	 *
	 * @var Variable_Convertor_Interface[]
	 */
	private array $convertors = [];

	/**
	 * Constructor.
	 *
	 * Initializes all available convertors.
	 */
	public function __construct() {
		$this->convertors = [
			new Color_Hex_Variable_Convertor(),
			new Color_Rgb_Variable_Convertor(),
			new Color_Rgba_Variable_Convertor(),
			new Color_Named_Variable_Convertor(),
			new Length_Size_Viewport_Variable_Convertor(),
			new Percentage_Variable_Convertor(),
		];
	}

	/**
	 * Register a custom convertor.
	 *
	 * @param Variable_Convertor_Interface $convertor Convertor instance.
	 * @return void
	 */
	public function register( Variable_Convertor_Interface $convertor ): void {
		$this->convertors[] = $convertor;
	}

	/**
	 * Find the appropriate convertor for a variable.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return Variable_Convertor_Interface|null Convertor or null if none found.
	 */
	public function resolve( string $name, string $value ): ?Variable_Convertor_Interface {
		foreach ( $this->convertors as $convertor ) {
			if ( $convertor->supports( $name, $value ) ) {
				return $convertor;
			}
		}

		return null;
	}
}
