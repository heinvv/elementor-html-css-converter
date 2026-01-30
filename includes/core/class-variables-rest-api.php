<?php
/**
 * Variables REST API
 *
 * REST API endpoint for importing CSS variables.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Core;

use ElementorHtmlCssConverter\Services\Variables\Variable_Extractor;
use ElementorHtmlCssConverter\Services\Variables\Variable_Conversion_Service;
use Elementor\Modules\Variables\Storage\Repository as Variables_Repository;
use Elementor\Plugin;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variables_Rest_API
 *
 * Handles the /import-variables REST endpoint.
 */
class Variables_Rest_API {

	/**
	 * Constructor.
	 *
	 * Registers the REST API route.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	/**
	 * Register the REST API route.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'html-css-converter/v1',
			'/import-variables',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'import_variables' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'css'         => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
					'url'         => [
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
					'update_mode' => [
						'type'              => 'string',
						'default'           => 'create_new',
						'enum'              => [ 'create_new', 'update' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Check permissions for the endpoint.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_permissions(): bool {
		// TESTING ONLY - Allow public access without authentication.
		// Remove this line and uncomment the next for production:
		return true;
		// return current_user_can( 'edit_posts' );
	}

	/**
	 * Handle the import variables request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function import_variables( WP_REST_Request $request ) {
		$url         = $request->get_param( 'url' );
		$css         = $request->get_param( 'css' );
		$update_mode = $request->get_param( 'update_mode' ) ?? 'create_new';

		// Validate that either css or url is provided
		if ( empty( $css ) && empty( $url ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Missing css or url',
					'code'  => 'invalid_request',
				],
				400
			);
		}

		// Fetch from URL if provided
		if ( ! empty( $url ) ) {
			$fetch_result = $this->fetch_css_from_url( $url );

			if ( is_wp_error( $fetch_result ) || $fetch_result instanceof WP_REST_Response ) {
				return $fetch_result;
			}

			$css = $fetch_result;
		}

		// Validate CSS is not empty
		if ( empty( $css ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Empty CSS',
					'code'  => 'empty_css',
				],
				422
			);
		}

		// Remove UTF-8 BOM if present
		$css = $this->remove_utf8_bom( $css );

		// Extract variables
		$extractor     = new Variable_Extractor();
		$raw_variables = $extractor->extract_from_css( $css );

		if ( empty( $raw_variables ) ) {
			return new WP_REST_Response(
				[
					'error' => 'No variables found in CSS',
					'code'  => 'no_variables',
				],
				422
			);
		}

		// Convert to Elementor format
		$converted = Variable_Conversion_Service::convert_to_editor_variables( $raw_variables );

		if ( empty( $converted ) ) {
			return new WP_REST_Response(
				[
					'error' => 'No supported variable types found',
					'code'  => 'no_supported_types',
				],
				422
			);
		}

		// Store in Elementor variables system
		try {
			$storage_result = $this->store_variables( $converted, $update_mode );
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'error'   => 'Failed to store variables',
					'code'    => 'storage_error',
					'details' => $e->getMessage(),
				],
				500
			);
		}

		// Build response
		$response_variables = $this->format_response_variables( $converted );

		return new WP_REST_Response(
			[
				'success'   => true,
				'variables' => $response_variables,
				'created'   => $storage_result['created'],
				'reused'    => $storage_result['reused'],
				'updated'   => $storage_result['updated'],
			],
			200
		);
	}

	/**
	 * Fetch CSS from URL.
	 *
	 * @param string $url URL to fetch from.
	 * @return string|WP_REST_Response|\WP_Error CSS content or error.
	 */
	private function fetch_css_from_url( string $url ) {
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				[
					'error'   => 'Fetch failed',
					'code'    => 'fetch_error',
					'details' => $response->get_error_message(),
				],
				502
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return new WP_REST_Response(
				[
					'error'   => 'Fetch failed',
					'code'    => 'http_error',
					'details' => 'HTTP ' . $code,
				],
				502
			);
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Remove UTF-8 BOM from string.
	 *
	 * @param string $css CSS string.
	 * @return string CSS without BOM.
	 */
	private function remove_utf8_bom( string $css ): string {
		if ( 0 === strpos( $css, "\xEF\xBB\xBF" ) ) {
			return substr( $css, 3 );
		}

		return $css;
	}

	/**
	 * Store variables in Elementor's variables system.
	 *
	 * @param array  $variables   Converted variables.
	 * @param string $update_mode Mode: 'create_new' or 'update'.
	 * @return array Storage result with 'created', 'reused', and 'updated' counts.
	 * @throws \Exception If Elementor is not active or storage fails.
	 */
	private function store_variables( array $variables, string $update_mode ): array {
		// Check if Elementor is available
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			throw new \Exception( 'Elementor plugin is not active' );
		}

		$repository = new Variables_Repository(
			Plugin::$instance->kits_manager->get_active_kit()
		);

		$created = 0;
		$reused  = 0;
		$updated = 0;

		foreach ( $variables as $variable ) {
			$name  = $variable['name'] ?? '';
			$value = $variable['value'] ?? '';
			$type  = $variable['type'] ?? '';

			if ( empty( $name ) || empty( $value ) ) {
				continue;
			}

			// Format label (remove -- prefix)
			$label = ltrim( $name, '-' );

			// Map convertor type to Elementor variable type
			$elementor_type = $this->map_type_to_elementor( $type );

			if ( null === $elementor_type ) {
				continue;
			}

			if ( 'update' === $update_mode ) {
				// Update mode: find existing or create new
				$existing_id = $this->find_variable_by_label( $repository, $label );

				if ( $existing_id ) {
					$repository->update(
						$existing_id,
						[
							'label' => $label,
							'value' => $value,
						]
					);
					++$updated;
				} else {
					$repository->create(
						[
							'type'  => $elementor_type,
							'label' => $label,
							'value' => $value,
						]
					);
					++$created;
				}
			} else {
				// create_new mode: check if value already exists, reuse if found
				$existing_match = $this->find_variable_by_base_label_and_value( $repository, $label, $value );

				if ( $existing_match ) {
					// Value already exists, don't create duplicate
					++$reused;
					continue;
				}

				// Value doesn't exist, create new with unique label
				$final_label = $this->get_unique_label( $repository, $label );

				$repository->create(
					[
						'type'  => $elementor_type,
						'label' => $final_label,
						'value' => $value,
					]
				);
				++$created;
			}
		}

		// Clear Elementor cache
		if ( isset( Plugin::$instance->files_manager ) ) {
			Plugin::$instance->files_manager->clear_cache();
		}

		return [
			'created' => $created,
			'reused'  => $reused,
			'updated' => $updated,
		];
	}

	/**
	 * Map convertor type to Elementor variable type.
	 *
	 * @param string $type Convertor type.
	 * @return string|null Elementor type or null.
	 */
	private function map_type_to_elementor( string $type ): ?string {
		$type_map = [
			'color-hex'             => 'global-color-variable',
			'color-rgb'             => 'global-color-variable',
			'color-rgba'            => 'global-color-variable',
			'size-length-viewport'  => 'global-size-variable',
			'size-percentage'       => 'global-size-variable',
		];

		return $type_map[ $type ] ?? null;
	}

	/**
	 * Find variable ID by label.
	 *
	 * @param Variables_Repository $repository Variables repository.
	 * @param string               $label      Variable label.
	 * @return string|null Variable ID or null.
	 */
	private function find_variable_by_label( Variables_Repository $repository, string $label ): ?string {
		$db_record = $repository->load();
		$existing  = isset( $db_record['data'] ) && is_array( $db_record['data'] ) ? $db_record['data'] : [];

		foreach ( $existing as $id => $item ) {
			if ( isset( $item['deleted'] ) && $item['deleted'] ) {
				continue;
			}

			if ( isset( $item['label'] ) && strtolower( $item['label'] ) === strtolower( $label ) ) {
				return $id;
			}
		}

		return null;
	}

	/**
	 * Find variable by base label and value.
	 *
	 * Searches for any variable matching the base label (with or without suffix)
	 * and the exact value. For example, if searching for "primary-color" with value "red",
	 * it will match "primary-color", "primary-color-1", "primary-color-2", etc.
	 *
	 * @param Variables_Repository $repository  Variables repository.
	 * @param string               $base_label  Base variable label (without suffix).
	 * @param string               $value       Variable value to match.
	 * @return array|null Variable data with 'id' and 'label' keys, or null if not found.
	 */
	private function find_variable_by_base_label_and_value( Variables_Repository $repository, string $base_label, string $value ): ?array {
		$db_record = $repository->load();
		$existing  = isset( $db_record['data'] ) && is_array( $db_record['data'] ) ? $db_record['data'] : [];

		$base_label_lower = strtolower( $base_label );

		foreach ( $existing as $id => $item ) {
			if ( isset( $item['deleted'] ) && $item['deleted'] ) {
				continue;
			}

			if ( ! isset( $item['label'] ) || ! isset( $item['value'] ) ) {
				continue;
			}

			$item_label_lower = strtolower( $item['label'] );

			// Check if label matches base label or base label with suffix (e.g., "primary-color-1")
			$matches_base = $item_label_lower === $base_label_lower;
			$matches_with_suffix = preg_match( '/^' . preg_quote( $base_label_lower, '/' ) . '-\d+$/', $item_label_lower );

			if ( ( $matches_base || $matches_with_suffix ) && $item['value'] === $value ) {
				return [
					'id'    => $id,
					'label' => $item['label'],
				];
			}
		}

		return null;
	}

	/**
	 * Get unique label with suffix if needed.
	 *
	 * @param Variables_Repository $repository Variables repository.
	 * @param string               $base_label Base label.
	 * @return string Unique label.
	 */
	private function get_unique_label( Variables_Repository $repository, string $base_label ): string {
		$db_record = $repository->load();
		$existing  = isset( $db_record['data'] ) && is_array( $db_record['data'] ) ? $db_record['data'] : [];

		$labels = [];
		foreach ( $existing as $item ) {
			if ( isset( $item['deleted'] ) && $item['deleted'] ) {
				continue;
			}

			if ( isset( $item['label'] ) ) {
				$labels[] = strtolower( $item['label'] );
			}
		}

		$label_lower = strtolower( $base_label );

		// If label doesn't exist, use it as-is
		if ( ! in_array( $label_lower, $labels, true ) ) {
			return $base_label;
		}

		// Find next available suffix
		$suffix = 1;
		while ( in_array( $label_lower . '-' . $suffix, $labels, true ) ) {
			++$suffix;
		}

		return $base_label . '-' . $suffix;
	}

	/**
	 * Format variables for response.
	 *
	 * @param array $converted Converted variables.
	 * @return array Formatted variables.
	 */
	private function format_response_variables( array $converted ): array {
		$formatted = [];

		foreach ( $converted as $variable ) {
			$name = $variable['name'] ?? '';

			if ( empty( $name ) ) {
				continue;
			}

			// Use name without -- as key
			$key = ltrim( $name, '-' );

			$formatted[ $key ] = [
				'name'  => $name,
				'value' => $variable['value'] ?? '',
				'type'  => $variable['type'] ?? '',
			];
		}

		return $formatted;
	}
}
