<?php
/**
 * Abstract Variable Convertor
 *
 * Base class for CSS variable convertors.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Convertor_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Abstract_Variable_Convertor
 *
 * Provides common functionality for all variable convertors.
 */
abstract class Abstract_Variable_Convertor implements Variable_Convertor_Interface {

	private const REGEX_SLUG_SANITIZATION = '/[^a-z0-9_\-]+/';

	/**
	 * Convert variable to Elementor format.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return array Converted variable data.
	 */
	public function convert( string $name, string $value ): array {
		return [
			'id'     => $this->generate_variable_id( $name ),
			'type'   => $this->get_type(),
			'value'  => $this->normalize_value( $value ),
			'source' => 'css-variable',
			'name'   => $name,
		];
	}

	/**
	 * Get the type identifier for this convertor.
	 *
	 * @return string Type (e.g., 'color-hex', 'size-length-viewport').
	 */
	abstract protected function get_type(): string;

	/**
	 * Normalize the variable value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized value.
	 */
	abstract protected function normalize_value( string $value ): string;

	/**
	 * Generate a unique ID for the variable.
	 *
	 * Format: e-gv-{type}-{slug}-variable
	 *
	 * @param string $name Variable name (e.g., '--primary-color').
	 * @return string Generated ID.
	 */
	private function generate_variable_id( string $name ): string {
		$trimmed = ltrim( $name, '-' );
		$slug    = strtolower( $trimmed );
		$slug    = preg_replace( self::REGEX_SLUG_SANITIZATION, '-', $slug );
		$slug    = trim( $slug, '-' );

		if ( '' === $slug ) {
			return 'e-gv-' . $this->get_type() . '-variable';
		}

		return 'e-gv-' . $this->get_type() . '-' . $slug . '-variable';
	}
}

