<?php
/**
 * Variable Resolver
 *
 * Resolves CSS var() references to Elementor global variable IDs.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables;

use Elementor\Modules\Variables\Storage\Repository as Variables_Repository;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variable_Resolver
 *
 * Resolves CSS variable references (var(--name)) to Elementor global variable IDs.
 */
class Variable_Resolver {

	private const REGEX_CSS_VARIABLE_EXTRACTION = '/^var\(\s*(--[a-zA-Z0-9_-]+)\s*(?:,.*?)?\)$/';

	/**
	 * Cached variables from repository.
	 *
	 * @var array|null
	 */
	private static ?array $cached_variables = null;

	/**
	 * Check if a value is a CSS variable reference.
	 *
	 * @param string $value The CSS value to check.
	 * @return bool True if value is a var() reference.
	 */
	public static function is_css_variable( string $value ): bool {
		return str_starts_with( trim( $value ), 'var(' );
	}

	/**
	 * Extract variable name from var() syntax.
	 *
	 * @param string $value The var() value (e.g., "var(--primary-color)").
	 * @return string|null The variable name without -- prefix, or null if invalid.
	 */
	public static function extract_variable_name( string $value ): ?string {
		$value = trim( $value );

		if ( preg_match( self::REGEX_CSS_VARIABLE_EXTRACTION, $value, $matches ) ) {
			return ltrim( $matches[1], '-' );
		}

		return null;
	}

	/**
	 * Resolve a CSS variable to an Elementor global variable ID.
	 *
	 * @param string $value The CSS value containing var().
	 * @param string $type  The expected variable type ('color' or 'size').
	 * @return array|null Resolved variable data or null if not found.
	 */
	public static function resolve( string $value, string $type = 'color' ): ?array {
		if ( ! self::is_css_variable( $value ) ) {
			return null;
		}

		$variable_label = self::extract_variable_name( $value );

		if ( null === $variable_label ) {
			return null;
		}

		$variable = self::find_variable_by_label( $variable_label );

		if ( null === $variable ) {
			return null;
		}

		$prop_type = self::get_prop_type_for_variable( $variable, $type );

		if ( null === $prop_type ) {
			return null;
		}

		return [
			'$$type' => $prop_type,
			'value'  => $variable['id'],
		];
	}

	/**
	 * Find a variable by its label in Elementor's repository.
	 *
	 * @param string $label The variable label (without -- prefix).
	 * @return array|null Variable data with 'id' key, or null if not found.
	 */
	private static function find_variable_by_label( string $label ): ?array {
		$variables = self::get_all_variables();

		$label_lower = strtolower( $label );

		foreach ( $variables as $id => $variable ) {
			if ( isset( $variable['deleted'] ) && $variable['deleted'] ) {
				continue;
			}

			if ( isset( $variable['label'] ) && strtolower( $variable['label'] ) === $label_lower ) {
				return array_merge( [ 'id' => $id ], $variable );
			}
		}

		return null;
	}

	/**
	 * Get all variables from Elementor's repository.
	 *
	 * @return array All variables indexed by ID.
	 */
	private static function get_all_variables(): array {
		if ( null !== self::$cached_variables ) {
			return self::$cached_variables;
		}

		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return [];
		}

		try {
			$repository = new Variables_Repository(
				Plugin::$instance->kits_manager->get_active_kit()
			);

			$db_record = $repository->load();
			self::$cached_variables = $db_record['data'] ?? [];
		} catch ( \Exception $e ) {
			self::$cached_variables = [];
		}

		return self::$cached_variables;
	}

	/**
	 * Get the prop type for a variable based on its Elementor type.
	 *
	 * @param array  $variable The variable data from repository.
	 * @param string $expected_type Expected type hint ('color' or 'size').
	 * @return string|null The $$type value or null if incompatible.
	 */
	private static function get_prop_type_for_variable( array $variable, string $expected_type ): ?string {
		$variable_type = $variable['type'] ?? '';

		$type_map = [
			'global-color-variable' => 'global-color-variable',
			'global-size-variable'  => 'global-size-variable',
		];

		if ( 'color' === $expected_type && 'global-color-variable' === $variable_type ) {
			return 'global-color-variable';
		}

		if ( 'size' === $expected_type && 'global-size-variable' === $variable_type ) {
			return 'global-size-variable';
		}

		return $type_map[ $variable_type ] ?? null;
	}

	/**
	 * Clear the cached variables.
	 *
	 * Call this after importing new variables.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$cached_variables = null;
	}
}

