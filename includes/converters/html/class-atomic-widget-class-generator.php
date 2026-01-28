<?php
/**
 * Atomic Widget Class Generator
 *
 * Generates unique class IDs for atomic widgets.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Atomic_Widget_Class_Generator
 *
 * Generates unique class IDs for widget styling.
 */
class Atomic_Widget_Class_Generator {

	/**
	 * Widget type to prefix mapping.
	 *
	 * @var array
	 */
	private array $widget_prefixes = [
		'e-heading'   => 'e',
		'e-paragraph' => 'e',
		'e-button'    => 'e',
		'e-image'     => 'e',
		'e-flexbox'   => 'e',
	];

	/**
	 * Generate a unique class ID.
	 *
	 * @param string $widget_type Optional widget type for prefix.
	 * @return string Generated class ID.
	 */
	public function generate_class_id( string $widget_type = '' ): string {
		$prefix = $this->get_widget_prefix( $widget_type );
		$hash1  = substr( md5( uniqid( '', true ) ), 0, 8 );
		$hash2  = substr( md5( microtime( true ) . wp_rand() ), 0, 7 );

		return "{$prefix}-{$hash1}-{$hash2}";
	}

	/**
	 * Generate multiple class IDs.
	 *
	 * @param array $widget_types Array of widget types.
	 * @return array Array of generated class IDs.
	 */
	public function generate_multiple_class_ids( array $widget_types ): array {
		$class_ids = [];

		foreach ( $widget_types as $widget_type ) {
			$class_ids[] = $this->generate_class_id( $widget_type );
		}

		return $class_ids;
	}

	/**
	 * Generate a unique class ID not in existing list.
	 *
	 * @param array  $existing_ids Existing class IDs to avoid.
	 * @param string $widget_type  Optional widget type.
	 * @return string Unique class ID.
	 */
	public function generate_unique_class_id( array $existing_ids, string $widget_type = '' ): string {
		$max_attempts = 100;
		$attempts     = 0;

		do {
			$class_id = $this->generate_class_id( $widget_type );
			++$attempts;
		} while ( in_array( $class_id, $existing_ids, true ) && $attempts < $max_attempts );

		if ( $attempts >= $max_attempts ) {
			$timestamp = time();
			$prefix    = $this->get_widget_prefix( $widget_type );
			$class_id  = "{$prefix}-{$timestamp}-" . substr( md5( uniqid( '', true ) ), 0, 8 );
		}

		return $class_id;
	}

	/**
	 * Get prefix for widget type.
	 *
	 * @param string $widget_type Widget type.
	 * @return string Prefix.
	 */
	private function get_widget_prefix( string $widget_type ): string {
		return $this->widget_prefixes[ $widget_type ] ?? 'e';
	}

	/**
	 * Validate class ID format.
	 *
	 * @param string $class_id Class ID to validate.
	 * @return bool True if valid.
	 */
	public function is_valid_class_id( string $class_id ): bool {
		$pattern = '/^[a-z]+-[a-f0-9]{8}-[a-f0-9]{7}$/';
		return 1 === preg_match( $pattern, $class_id );
	}

	/**
	 * Get supported widget types.
	 *
	 * @return array List of supported widget types.
	 */
	public function get_supported_widget_types(): array {
		return array_keys( $this->widget_prefixes );
	}

	/**
	 * Add a widget type prefix mapping.
	 *
	 * @param string $widget_type Widget type.
	 * @param string $prefix      Prefix to use.
	 */
	public function add_widget_type_prefix( string $widget_type, string $prefix ): void {
		$this->widget_prefixes[ $widget_type ] = $prefix;
	}
}
