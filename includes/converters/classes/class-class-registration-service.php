<?php
/**
 * Class Registration Service
 *
 * Service that registers converted CSS classes into Elementor's Global Classes system.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use Elementor\Modules\GlobalClasses\Global_Classes_Repository;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Class_Registration_Service
 *
 * Handles storing CSS classes in Elementor's Global Classes.
 */
class Class_Registration_Service {

	/**
	 * Maximum number of global classes.
	 *
	 * @var int
	 */
	private const MAX_CLASSES_LIMIT = 100;

	/**
	 * Maximum label length.
	 *
	 * @var int
	 */
	private const MAX_LABEL_LENGTH = 50;

	/**
	 * Source identifier for imported classes.
	 *
	 * @var string
	 */
	private const SOURCE_CSS_CONVERTER = 'html-css-converter';

	/**
	 * Register converted classes with Elementor's Global Classes.
	 *
	 * @param array  $converted_classes Converted classes from Class_Conversion_Service.
	 * @param string $update_mode       Mode: 'create_new' or 'update'.
	 * @param string $context           Context: 'frontend' or 'preview'.
	 * @return array Result with 'registered', 'skipped', 'updated', 'overflow', 'classes'.
	 */
	public function register_with_elementor( array $converted_classes, string $update_mode, string $context ): array {
		// Check if Elementor and Global Classes are available.
		if ( ! $this->is_global_classes_available() ) {
			return [
				'success'    => false,
				'error'      => 'Global Classes Module not available',
				'registered' => 0,
				'skipped'    => 0,
				'updated'    => 0,
				'overflow'   => [],
				'classes'    => [],
			];
		}

		$repository = Global_Classes_Repository::make()->context( $context );
		$existing   = $repository->all();

		$existing_items = $existing->get_items()->all();
		$existing_order = $existing->get_order()->all();

		$registered = 0;
		$skipped    = 0;
		$updated    = 0;
		$overflow   = [];
		$classes    = [];

		$existing_labels = $this->extract_existing_labels( $existing_items );
		$available_slots = self::MAX_CLASSES_LIMIT - count( $existing_items );

		foreach ( $converted_classes as $class_name => $class_data ) {
			$atomic_props = $class_data['atomic_props'] ?? [];
			$custom_css   = $class_data['custom_css'] ?? null;

			// Skip empty conversions.
			if ( empty( $atomic_props ) && empty( $custom_css ) ) {
				++$skipped;
				continue;
			}

			// Find existing class by label.
			$existing_id = $this->find_class_by_label( $existing_items, $class_name );

			if ( $existing_id ) {
				if ( 'update' === $update_mode ) {
					// Update existing class.
					$existing_items[ $existing_id ] = $this->create_class_config(
						$existing_id,
						$class_name,
						$atomic_props,
						$custom_css
					);
					++$updated;

					$classes[ $class_name ] = [
						'id'           => $class_name,
						'label'        => $class_name,
						'elementor_id' => $existing_id,
						'props'        => $atomic_props,
						'custom_css'   => $custom_css,
						'status'       => 'updated',
					];
				} else {
					// create_new mode: check if styles are identical.
					$existing_props = $this->extract_props_from_class( $existing_items[ $existing_id ] );

					if ( $this->are_styles_identical( $atomic_props, $existing_props ) ) {
						// Identical, silently reuse.
						++$skipped;

						$classes[ $class_name ] = [
							'id'           => $class_name,
							'label'        => $class_name,
							'elementor_id' => $existing_id,
							'props'        => $atomic_props,
							'custom_css'   => $custom_css,
							'status'       => 'reused',
						];
					} else {
						// Different styles, create with suffix.
						if ( $available_slots <= 0 ) {
							$overflow[] = $class_name;
							continue;
						}

						$unique_label = $this->get_unique_label( $class_name, $existing_labels );
						$new_id       = $this->generate_class_id( $unique_label );

						$existing_items[ $new_id ] = $this->create_class_config(
							$new_id,
							$unique_label,
							$atomic_props,
							$custom_css
						);
						$existing_order[]   = $new_id;
						$existing_labels[]  = strtolower( $unique_label );

						++$registered;
						--$available_slots;

						$classes[ $class_name ] = [
							'id'           => $class_name,
							'label'        => $unique_label,
							'elementor_id' => $new_id,
							'props'        => $atomic_props,
							'custom_css'   => $custom_css,
							'status'       => 'created_with_suffix',
						];
					}
				}
			} else {
				// New class.
				if ( $available_slots <= 0 ) {
					$overflow[] = $class_name;
					continue;
				}

				$label  = $this->truncate_label( $class_name );
				$new_id = $this->generate_class_id( $label );

				// Ensure ID is unique.
				$counter = 1;
				while ( isset( $existing_items[ $new_id ] ) ) {
					$new_id = $this->generate_class_id( $label ) . '-' . $counter;
					++$counter;
				}

				$existing_items[ $new_id ] = $this->create_class_config(
					$new_id,
					$label,
					$atomic_props,
					$custom_css
				);
				$existing_order[]   = $new_id;
				$existing_labels[]  = strtolower( $label );

				++$registered;
				--$available_slots;

				$classes[ $class_name ] = [
					'id'           => $class_name,
					'label'        => $label,
					'elementor_id' => $new_id,
					'props'        => $atomic_props,
					'custom_css'   => $custom_css,
					'status'       => 'created',
				];
			}
		}

		// Save to repository if there were any changes.
		if ( $registered > 0 || $updated > 0 ) {
			try {
				$repository->put( $existing_items, $existing_order );
				$this->clear_elementor_cache();
			} catch ( \Exception $e ) {
				return [
					'success'    => false,
					'error'      => 'Failed to save: ' . $e->getMessage(),
					'registered' => 0,
					'skipped'    => $skipped,
					'updated'    => 0,
					'overflow'   => $overflow,
					'classes'    => $classes,
				];
			}
		}

		return [
			'success'    => true,
			'registered' => $registered,
			'skipped'    => $skipped,
			'updated'    => $updated,
			'overflow'   => $overflow,
			'classes'    => $classes,
		];
	}

	/**
	 * Check if Global Classes module is available.
	 *
	 * @return bool True if available.
	 */
	private function is_global_classes_available(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& class_exists( '\Elementor\Modules\GlobalClasses\Global_Classes_Repository' );
	}

	/**
	 * Extract existing labels from items.
	 *
	 * @param array $items Existing global class items.
	 * @return array Lowercase labels.
	 */
	private function extract_existing_labels( array $items ): array {
		$labels = [];

		foreach ( $items as $item ) {
			if ( isset( $item['label'] ) ) {
				$labels[] = strtolower( $item['label'] );
			}
		}

		return $labels;
	}

	/**
	 * Find class ID by label.
	 *
	 * @param array  $items Existing global class items.
	 * @param string $label Label to find.
	 * @return string|null Class ID or null.
	 */
	private function find_class_by_label( array $items, string $label ): ?string {
		$label_lower = strtolower( $label );

		foreach ( $items as $id => $item ) {
			if ( isset( $item['label'] ) && strtolower( $item['label'] ) === $label_lower ) {
				return $id;
			}
		}

		return null;
	}

	/**
	 * Extract props from existing class.
	 *
	 * @param array $class_config Class configuration.
	 * @return array Props array.
	 */
	private function extract_props_from_class( array $class_config ): array {
		$variants = $class_config['variants'] ?? [];

		if ( empty( $variants ) ) {
			return [];
		}

		// Get desktop variant (first variant or the one with desktop breakpoint).
		foreach ( $variants as $variant ) {
			$meta = $variant['meta'] ?? [];
			if ( ( $meta['breakpoint'] ?? '' ) === 'desktop' && ( $meta['state'] ?? null ) === null ) {
				return $variant['props'] ?? [];
			}
		}

		// Fallback to first variant.
		return $variants[0]['props'] ?? [];
	}

	/**
	 * Check if two props arrays are identical.
	 *
	 * @param array $props1 First props array.
	 * @param array $props2 Second props array.
	 * @return bool True if identical.
	 */
	private function are_styles_identical( array $props1, array $props2 ): bool {
		// Sort arrays for comparison.
		ksort( $props1 );
		ksort( $props2 );

		return wp_json_encode( $props1 ) === wp_json_encode( $props2 );
	}

	/**
	 * Get unique label with suffix if needed.
	 *
	 * @param string $base_label Base label.
	 * @param array  $existing_labels Existing labels (lowercase).
	 * @return string Unique label.
	 */
	private function get_unique_label( string $base_label, array $existing_labels ): string {
		$base_label = $this->truncate_label( $base_label );
		$label_lower = strtolower( $base_label );

		// If label doesn't exist, use it.
		if ( ! in_array( $label_lower, $existing_labels, true ) ) {
			return $base_label;
		}

		// Find next available suffix.
		$suffix = 2;
		while ( in_array( $label_lower . '-' . $suffix, $existing_labels, true ) ) {
			++$suffix;
		}

		$unique = $base_label . '-' . $suffix;

		// Ensure still within length limit.
		return $this->truncate_label( $unique );
	}

	/**
	 * Truncate label to max length.
	 *
	 * @param string $label Label to truncate.
	 * @return string Truncated label.
	 */
	private function truncate_label( string $label ): string {
		if ( strlen( $label ) <= self::MAX_LABEL_LENGTH ) {
			return $label;
		}

		return substr( $label, 0, self::MAX_LABEL_LENGTH );
	}

	/**
	 * Generate class ID from label.
	 *
	 * @param string $label Class label.
	 * @return string Class ID.
	 */
	private function generate_class_id( string $label ): string {
		return sanitize_key( $label );
	}

	/**
	 * Create class configuration.
	 *
	 * @param string      $id          Class ID.
	 * @param string      $label       Class label.
	 * @param array       $atomic_props Atomic props.
	 * @param string|null $custom_css  Custom CSS.
	 * @return array Class configuration.
	 */
	private function create_class_config( string $id, string $label, array $atomic_props, ?string $custom_css = null ): array {
		$config = [
			'id'       => $id,
			'label'    => $label,
			'type'     => 'class',
			'variants' => [
				[
					'meta'  => [
						'breakpoint' => 'desktop',
						'state'      => null,
					],
					'props' => $atomic_props,
				],
			],
			'meta'     => [
				'source'      => self::SOURCE_CSS_CONVERTER,
				'imported_at' => time(),
			],
		];

		if ( ! empty( $custom_css ) ) {
			$config['variants'][0]['custom_css'] = [
				'raw' => class_exists( '\Elementor\Utils' )
					? \Elementor\Utils::encode_string( $custom_css )
					: base64_encode( $custom_css ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			];
		}

		return $config;
	}

	/**
	 * Clear Elementor cache.
	 *
	 * @return void
	 */
	private function clear_elementor_cache(): void {
		if ( isset( Plugin::$instance->files_manager ) ) {
			Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Get repository statistics.
	 *
	 * @param string $context Context: 'frontend' or 'preview'.
	 * @return array Statistics.
	 */
	public function get_repository_stats( string $context = 'frontend' ): array {
		if ( ! $this->is_global_classes_available() ) {
			return [
				'available'       => false,
				'error'           => 'Global Classes Module not available',
				'total_classes'   => 0,
				'available_slots' => 0,
			];
		}

		$repository = Global_Classes_Repository::make()->context( $context );
		$existing   = $repository->all();
		$count      = count( $existing->get_items()->all() );

		return [
			'available'       => true,
			'total_classes'   => $count,
			'available_slots' => self::MAX_CLASSES_LIMIT - $count,
			'max_limit'       => self::MAX_CLASSES_LIMIT,
		];
	}
}
