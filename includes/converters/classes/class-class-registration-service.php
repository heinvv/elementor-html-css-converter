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
	private const REGEX_LABEL_WITH_SUFFIX = '/^%s-\d+$/';

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
	 * Register breakpoint-aware classes with Elementor's Global Classes.
	 *
	 * @param array  $converted_classes Breakpoint-aware converted classes.
	 * @param string $update_mode       Mode: 'create_new' or 'update'.
	 * @param string $context           Context: 'frontend' or 'preview'.
	 * @return array Result with 'registered', 'skipped', 'updated', 'overflow', 'classes'.
	 */
	public function register_with_elementor( array $converted_classes, string $update_mode, string $context ): array {
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
			$breakpoint_props = $class_data['breakpoint_props'] ?? [];

			if ( empty( $breakpoint_props ) ) {
				++$skipped;
				continue;
			}

			$has_content = false;
			foreach ( $breakpoint_props as $breakpoint_data ) {
				if ( ! empty( $breakpoint_data['atomic_props'] ) || ! empty( $breakpoint_data['custom_css'] ) ) {
					$has_content = true;
					break;
				}
			}

			if ( ! $has_content ) {
				++$skipped;
				continue;
			}

			$existing_id = $this->find_class_by_label( $existing_items, $class_name );

			if ( $existing_id ) {
				if ( 'update' === $update_mode ) {
					$existing_items[ $existing_id ] = $this->create_class_config_with_breakpoints(
						$existing_id,
						$class_name,
						$breakpoint_props
					);
					++$updated;

					$classes[ $class_name ] = [
						'id'             => $class_name,
						'label'          => $class_name,
						'elementor_id'   => $existing_id,
						'breakpoint_props' => $breakpoint_props,
						'status'         => 'updated',
					];
				} else {
					$existing_props = $this->extract_props_from_class( $existing_items[ $existing_id ] );
					$desktop_props  = $breakpoint_props['desktop']['atomic_props'] ?? [];

					if ( $this->are_styles_identical( $desktop_props, $existing_props ) ) {
						++$skipped;

						$classes[ $class_name ] = [
							'id'             => $class_name,
							'label'          => $class_name,
							'elementor_id'   => $existing_id,
							'breakpoint_props' => $breakpoint_props,
							'status'         => 'reused',
						];
						continue;
					}

					$desktop_props = $breakpoint_props['desktop']['atomic_props'] ?? [];
					$existing_match = $this->find_class_by_base_label_and_styles( $existing_items, $class_name, $desktop_props );

					if ( $existing_match ) {
						++$skipped;

						$classes[ $class_name ] = [
							'id'             => $class_name,
							'label'          => $existing_match['label'],
							'elementor_id'   => $existing_match['id'],
							'breakpoint_props' => $breakpoint_props,
							'status'         => 'reused',
						];
					} else {
						if ( $available_slots <= 0 ) {
							$overflow[] = $class_name;
							continue;
						}

						$unique_label = $this->get_unique_label( $class_name, $existing_labels );
						$new_id       = $this->generate_class_id( $unique_label );

						$existing_items[ $new_id ] = $this->create_class_config_with_breakpoints(
							$new_id,
							$unique_label,
							$breakpoint_props
						);
						$existing_order[]   = $new_id;
						$existing_labels[]  = strtolower( $unique_label );

						++$registered;
						--$available_slots;

						$classes[ $class_name ] = [
							'id'             => $class_name,
							'label'          => $unique_label,
							'elementor_id'   => $new_id,
							'breakpoint_props' => $breakpoint_props,
							'status'         => 'created_with_suffix',
						];
					}
				}
			} else {
				$desktop_props = $breakpoint_props['desktop']['atomic_props'] ?? [];
				$existing_match = $this->find_class_by_base_label_and_styles( $existing_items, $class_name, $desktop_props );

				if ( $existing_match ) {
					++$skipped;

					$classes[ $class_name ] = [
						'id'             => $class_name,
						'label'          => $existing_match['label'],
						'elementor_id'   => $existing_match['id'],
						'breakpoint_props' => $breakpoint_props,
						'status'         => 'reused',
					];
				} else {
					if ( $available_slots <= 0 ) {
						$overflow[] = $class_name;
						continue;
					}

					$label  = $this->truncate_label( $class_name );
					$new_id = $this->generate_class_id( $label );

					$counter = 1;
					while ( isset( $existing_items[ $new_id ] ) ) {
						$new_id = $this->generate_class_id( $label ) . '-' . $counter;
						++$counter;
					}

					$existing_items[ $new_id ] = $this->create_class_config_with_breakpoints(
						$new_id,
						$label,
						$breakpoint_props
					);
					$existing_order[]   = $new_id;
					$existing_labels[]  = strtolower( $label );

					++$registered;
					--$available_slots;

					$classes[ $class_name ] = [
						'id'             => $class_name,
						'label'          => $label,
						'elementor_id'   => $new_id,
						'breakpoint_props' => $breakpoint_props,
						'status'         => 'created',
					];
				}
			}
		}

		$save_error = $this->save_changes_to_repository_if_needed(
			$repository,
			$existing_items,
			$existing_order,
			$registered,
			$updated,
			$skipped,
			$overflow,
			$classes
		);

		if ( $save_error ) {
			return $save_error;
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
	 * Save changes to repository if there were any registered or updated classes.
	 *
	 * @param Global_Classes_Repository $repository     Repository instance.
	 * @param array                      $existing_items Existing items to save.
	 * @param array                      $existing_order Existing order to save.
	 * @param int                        $registered     Number of registered classes.
	 * @param int                        $updated        Number of updated classes.
	 * @param int                        $skipped        Number of skipped classes.
	 * @param array                      $overflow       Overflow classes.
	 * @param array                      $classes        Classes array.
	 * @return array|null Error array on failure, null on success.
	 */
	private function save_changes_to_repository_if_needed(
		Global_Classes_Repository $repository,
		array $existing_items,
		array $existing_order,
		int $registered,
		int $updated,
		int $skipped,
		array $overflow,
		array $classes
	): ?array {
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

		return null;
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
	 * Find class by base label and styles.
	 *
	 * Searches for any class matching the base label (with or without suffix)
	 * and identical styles. For example, if searching for "btn" with certain styles,
	 * it will match "btn", "btn-2", "btn-3", etc.
	 *
	 * @param array  $items        Existing global class items.
	 * @param string $base_label   Base class label (without suffix).
	 * @param array  $atomic_props Atomic props to match.
	 * @return array|null Class data with 'id' and 'label' keys, or null if not found.
	 */
	private function find_class_by_base_label_and_styles( array $items, string $base_label, array $atomic_props ): ?array {
		$base_label_lower = strtolower( $base_label );

		foreach ( $items as $id => $item ) {
			if ( ! isset( $item['label'] ) ) {
				continue;
			}

			$match = $this->find_matching_class_by_label_and_styles( $item, $id, $base_label_lower, $atomic_props );
			if ( $match ) {
				return $match;
			}
		}

		return null;
	}

	/**
	 * Find matching class by label pattern and identical styles.
	 *
	 * @param array  $item            Item to check.
	 * @param string $id              Item ID.
	 * @param string $base_label_lower Base label in lowercase.
	 * @param array  $atomic_props    Atomic props to match.
	 * @return array|null Class data with 'id' and 'label' keys, or null if no match.
	 */
	private function find_matching_class_by_label_and_styles( array $item, string $id, string $base_label_lower, array $atomic_props ): ?array {
		$item_label_lower = strtolower( $item['label'] );

		$matches_base        = $item_label_lower === $base_label_lower;
		$matches_with_suffix = preg_match( '/^' . preg_quote( $base_label_lower, '/' ) . '-\d+$/', $item_label_lower );

		if ( ! $matches_base && ! $matches_with_suffix ) {
			return null;
		}

		$existing_props = $this->extract_props_from_class( $item );
		if ( ! $this->are_styles_identical( $atomic_props, $existing_props ) ) {
			return null;
		}

		return [
			'id'    => $id,
			'label' => $item['label'],
		];
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

		$desktop_props = $this->get_desktop_variant_props_from_variants( $variants );
		if ( $desktop_props !== null ) {
			return $desktop_props;
		}

		return $this->get_first_variant_props( $variants );
	}

	/**
	 * Get desktop variant props from variants array.
	 *
	 * @param array $variants Variants array.
	 * @return array|null Props array if desktop variant found, null otherwise.
	 */
	private function get_desktop_variant_props_from_variants( array $variants ): ?array {
		foreach ( $variants as $variant ) {
			$meta = $variant['meta'] ?? [];
			if ( ( $meta['breakpoint'] ?? '' ) === 'desktop' && ( $meta['state'] ?? null ) === null ) {
				return $variant['props'] ?? [];
			}
		}

		return null;
	}

	/**
	 * Get first variant props as fallback.
	 *
	 * @param array $variants Variants array.
	 * @return array Props array from first variant.
	 */
	private function get_first_variant_props( array $variants ): array {
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

		if ( ! in_array( $label_lower, $existing_labels, true ) ) {
			return $base_label;
		}

		$suffix = $this->find_next_available_suffix( $label_lower, $existing_labels );

		return $this->create_unique_label_with_suffix( $base_label, $suffix );
	}

	/**
	 * Find next available suffix for label.
	 *
	 * @param string $label_lower     Label in lowercase.
	 * @param array  $existing_labels Existing labels (lowercase).
	 * @return int Next available suffix number.
	 */
	private function find_next_available_suffix( string $label_lower, array $existing_labels ): int {
		$suffix = 2;
		while ( in_array( $label_lower . '-' . $suffix, $existing_labels, true ) ) {
			++$suffix;
		}

		return $suffix;
	}

	/**
	 * Create unique label with suffix and ensure it's within length limit.
	 *
	 * @param string $base_label Base label.
	 * @param int    $suffix     Suffix number.
	 * @return string Unique label truncated to max length.
	 */
	private function create_unique_label_with_suffix( string $base_label, int $suffix ): string {
		$unique = $base_label . '-' . $suffix;

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
	 * Create class configuration with breakpoints.
	 *
	 * @param string $id               Class ID.
	 * @param string $label            Class label.
	 * @param array  $breakpoint_props Breakpoint-aware atomic props.
	 *                                 Format: ['desktop' => ['atomic_props' => [...], 'custom_css' => ...], ...]
	 * @return array Class configuration with multiple variants.
	 */
	private function create_class_config_with_breakpoints( string $id, string $label, array $breakpoint_props ): array {
		$variants = [];

		if ( isset( $breakpoint_props['desktop'] ) ) {
			$desktop_data = $breakpoint_props['desktop'];
			$desktop_props = $desktop_data['atomic_props'] ?? [];
			$desktop_css   = $desktop_data['custom_css'] ?? null;

			$variant = [
				'meta'  => [
					'breakpoint' => 'desktop',
					'state'      => null,
				],
				'props' => $desktop_props,
			];

			if ( ! empty( $desktop_css ) ) {
				$variant['custom_css'] = [
					'raw' => class_exists( '\Elementor\Utils' )
						? \Elementor\Utils::encode_string( $desktop_css )
						: base64_encode( $desktop_css ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				];
			}

			$variants[] = $variant;
		}

		$breakpoint_order = [ 'tablet', 'mobile', 'mobile_extra', 'tablet_extra', 'laptop', 'widescreen' ];

		foreach ( $breakpoint_order as $breakpoint ) {
			if ( isset( $breakpoint_props[ $breakpoint ] ) ) {
				$breakpoint_data = $breakpoint_props[ $breakpoint ];
				$breakpoint_props_data = $breakpoint_data['atomic_props'] ?? [];
				$breakpoint_css        = $breakpoint_data['custom_css'] ?? null;

				if ( ! empty( $breakpoint_props_data ) || ! empty( $breakpoint_css ) ) {
					$variant = [
						'meta'  => [
							'breakpoint' => $breakpoint,
							'state'      => null,
						],
						'props' => $breakpoint_props_data,
					];

					if ( ! empty( $breakpoint_css ) ) {
						$variant['custom_css'] = [
							'raw' => class_exists( '\Elementor\Utils' )
								? \Elementor\Utils::encode_string( $breakpoint_css )
								: base64_encode( $breakpoint_css ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						];
					}

					$variants[] = $variant;
				}
			}
		}

		if ( empty( $variants ) ) {
			$variants[] = [
				'meta'  => [
					'breakpoint' => 'desktop',
					'state'      => null,
				],
				'props' => [],
			];
		}

		return [
			'id'       => $id,
			'label'    => $label,
			'type'     => 'class',
			'variants' => $variants,
			'meta'     => [
				'source'      => self::SOURCE_CSS_CONVERTER,
				'imported_at' => time(),
			],
		];
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
