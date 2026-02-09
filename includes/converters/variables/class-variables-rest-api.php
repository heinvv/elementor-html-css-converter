<?php
/**
 * Variables REST API
 *
 * REST API endpoint for importing CSS variables.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables;

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

	private const REGEX_LABEL_WITH_SUFFIX = '/^%s-\d+$/';
	private const REGEX_LABEL_WITH_ANY_SUFFIX = '/^%s-.+$/';

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

		$validation_error = $this->validate_css_or_url_provided( $css, $url );
		if ( $validation_error ) {
			return $validation_error;
		}

		$css_result = $this->fetch_css_from_url_if_provided( $url, $css );
		if ( $css_result instanceof WP_REST_Response || is_wp_error( $css_result ) ) {
			return $css_result;
		}
		$css = $css_result;

		$empty_css_error = $this->validate_css_not_empty( $css );
		if ( $empty_css_error ) {
			return $empty_css_error;
		}

		$css = $this->remove_utf8_bom( $css );

		$extract_result = $this->extract_and_convert_variables_from_css( $css );
		if ( $extract_result instanceof WP_REST_Response ) {
			return $extract_result;
		}

		$converted = $extract_result;

		if ( empty( $converted ) ) {
			return new WP_REST_Response(
				[
					'error' => 'No supported variable types found',
					'code'  => 'no_supported_types',
				],
				422
			);
		}

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
	 * Validate that either css or url is provided.
	 *
	 * @param string|null $css CSS string.
	 * @param string|null $url URL string.
	 * @return WP_REST_Response|null Error response if validation fails, null otherwise.
	 */
	private function validate_css_or_url_provided( ?string $css, ?string $url ): ?WP_REST_Response {
		if ( empty( $css ) && empty( $url ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Missing css or url',
					'code'  => 'invalid_request',
				],
				400
			);
		}

		return null;
	}

	/**
	 * Fetch CSS from URL if provided.
	 *
	 * @param string|null $url URL to fetch from.
	 * @param string|null $css Existing CSS string.
	 * @return string|WP_REST_Response|\WP_Error CSS content or error response.
	 */
	private function fetch_css_from_url_if_provided( ?string $url, ?string $css ) {
		if ( empty( $url ) ) {
			return $css;
		}

		return $this->fetch_css_from_url( $url );
	}

	/**
	 * Validate CSS is not empty.
	 *
	 * @param string|null $css CSS string.
	 * @return WP_REST_Response|null Error response if CSS is empty, null otherwise.
	 */
	private function validate_css_not_empty( ?string $css ): ?WP_REST_Response {
		if ( empty( $css ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Empty CSS',
					'code'  => 'empty_css',
				],
				422
			);
		}

		return null;
	}

	/**
	 * Extract and convert variables from CSS.
	 *
	 * @param string $css CSS content.
	 * @return array|WP_REST_Response Converted variables array or error response.
	 */
	private function extract_and_convert_variables_from_css( string $css ) {
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

		return Variable_Conversion_Service::convert_to_editor_variables( $raw_variables );
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
	 * @return array Storage result with 'created', 'reused', 'updated' counts, and 'renames' mapping.
	 *               'renames' maps original CSS variable names to final names (e.g., '--color' => '--color-1').
	 * @throws \Exception If Elementor is not active or storage fails.
	 */
	public function store_variables( array $variables, string $update_mode ): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			throw new \Exception( 'Elementor plugin is not active' );
		}

		$repository = new Variables_Repository(
			Plugin::$instance->kits_manager->get_active_kit()
		);

		$created = 0;
		$reused  = 0;
		$updated = 0;
		$renames = [];

		foreach ( $variables as $variable ) {
			$name  = $variable['name'] ?? '';
			$value = $variable['value'] ?? '';
			$type  = $variable['type'] ?? '';

			if ( empty( $name ) || empty( $value ) ) {
				continue;
			}

			$label = ltrim( $name, '-' );

			$elementor_type = $this->map_type_to_elementor( $type );

			if ( null === $elementor_type ) {
				continue;
			}

			if ( 'update' === $update_mode ) {
				$result = $this->handle_update_mode( $repository, $label, $value, $elementor_type );
				$created += $result['created'];
				$updated += $result['updated'];
			} else {
				$result = $this->handle_create_mode( $repository, $label, $value, $elementor_type );
				$created += $result['created'];
				$reused += $result['reused'];
				if ( ! empty( $result['rename'] ) ) {
					$renames[ $result['rename']['from'] ] = $result['rename']['to'];
				}
			}
		}

		if ( isset( Plugin::$instance->files_manager ) ) {
			Plugin::$instance->files_manager->clear_cache();
		}

		return [
			'created' => $created,
			'reused'  => $reused,
			'updated' => $updated,
			'renames' => $renames,
		];
	}

	/**
	 * Handle update mode for a variable.
	 *
	 * @param Variables_Repository $repository Variables repository.
	 * @param string               $label      Variable label.
	 * @param string               $value      Variable value.
	 * @param string               $elementor_type Elementor variable type.
	 * @return array Result with 'created' and 'updated' counts.
	 */
	private function handle_update_mode( Variables_Repository $repository, string $label, string $value, string $elementor_type ): array {
		$existing_id = $this->find_variable_by_label( $repository, $label );

		if ( $existing_id ) {
			$repository->update(
				$existing_id,
				[
					'label' => $label,
					'value' => $value,
				]
			);
			return [ 'created' => 0, 'updated' => 1 ];
		}

		$repository->create(
			[
				'type'  => $elementor_type,
				'label' => $label,
				'value' => $value,
			]
		);
		return [ 'created' => 1, 'updated' => 0 ];
	}

	/**
	 * Handle create mode for a variable.
	 *
	 * @param Variables_Repository $repository Variables repository.
	 * @param string               $label      Variable label.
	 * @param string               $value      Variable value.
	 * @param string               $elementor_type Elementor variable type.
	 * @return array Result with 'created', 'reused' counts and optional 'rename' array.
	 */
	private function handle_create_mode( Variables_Repository $repository, string $label, string $value, string $elementor_type ): array {
		$existing_match = $this->find_variable_by_base_label_and_value( $repository, $label, $value );

		if ( $existing_match ) {
			$existing_label = $existing_match['label'];
			$rename = null;
			if ( strtolower( $existing_label ) !== strtolower( $label ) ) {
				$rename = [
					'from' => '--' . $label,
					'to'   => '--' . $existing_label,
				];
			}
			return [
				'created' => 0,
				'reused'  => 1,
				'rename'  => $rename,
			];
		}

		$final_label = $this->get_unique_label( $repository, $label );

		$repository->create(
			[
				'type'  => $elementor_type,
				'label' => $final_label,
				'value' => $value,
			]
		);

		$rename = null;
		if ( $final_label !== $label ) {
			$rename = [
				'from' => '--' . $label,
				'to'   => '--' . $final_label,
			];
		}

		return [
			'created' => 1,
			'reused'  => 0,
			'rename'  => $rename,
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
		$base_label_quoted = preg_quote( $base_label_lower, '/' );

		foreach ( $existing as $id => $item ) {
			if ( isset( $item['deleted'] ) && $item['deleted'] ) {
				continue;
			}

			if ( ! isset( $item['label'] ) || ! isset( $item['value'] ) ) {
				continue;
			}

			if ( $item['value'] !== $value ) {
				continue;
			}

			$item_label_lower = strtolower( $item['label'] );

			$matches_base = $item_label_lower === $base_label_lower;
			$matches_with_numeric_suffix = preg_match( sprintf( self::REGEX_LABEL_WITH_SUFFIX, $base_label_quoted ), $item_label_lower );
			$matches_with_any_suffix = preg_match( sprintf( self::REGEX_LABEL_WITH_ANY_SUFFIX, $base_label_quoted ), $item_label_lower );

			if ( $matches_base || $matches_with_numeric_suffix || $matches_with_any_suffix ) {
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

		if ( ! in_array( $label_lower, $labels, true ) ) {
			return $base_label;
		}

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

