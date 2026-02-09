<?php
/**
 * Image Import Service
 *
 * Service for importing external images into WordPress media library.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Images;

use Elementor\Plugin;
use Elementor\Core\Files\Uploads_Manager;
use Elementor\Core\Files\File_Types\Svg;
use Elementor\User;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use ElementorHtmlCssConverter\Converters\Images\Svg_Security_Bypass_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Image_Import_Service
 *
 * Handles importing external images into WordPress media library.
 */
class Image_Import_Service {

	/**
	 * Security bypass handler instance.
	 *
	 * @var Svg_Security_Bypass_Handler
	 */
	private Svg_Security_Bypass_Handler $security_bypass_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->security_bypass_handler = new Svg_Security_Bypass_Handler();
	}

	/**
	 * Check if SVG imports are allowed.
	 *
	 * @return array Array with 'allowed' boolean and 'warnings' array.
	 */
	public function check_svg_import_permissions(): array {
		$this->ensure_svg_mime_type_registered();
		
		$warnings = [];

		$unfiltered_enabled = $this->security_bypass_handler->are_unfiltered_uploads_enabled();
		
		if ( ! $unfiltered_enabled ) {
			$warnings[] = 'SVG import requires "Enable Unfiltered File Uploads" to be enabled in Elementor > Settings > Advanced';
		}

		$sanitizer_can_run = Svg::file_sanitizer_can_run();
		if ( ! $sanitizer_can_run ) {
			$warnings[] = 'SVG import requires PHP classes DOMDocument and SimpleXMLElement to be available';
		}

		$can_upload_json = $this->security_bypass_handler->can_upload_json();
		
		if ( ! $can_upload_json ) {
			$warnings[] = 'SVG import requires user to have manage_options capability or Elementor role manager to allow JSON uploads';
		}

		$mimes = get_allowed_mime_types();
		$has_svg_mime = isset( $mimes['svg'] ) || isset( $mimes['svgz'] );
		
		if ( ! $has_svg_mime ) {
			$has_filter = has_filter( 'upload_mimes', [ $this, 'add_svg_mime_type' ] );
			if ( $this->security_bypass_handler->should_suppress_mime_type_warning( $has_filter ) ) {
			} else {
				$safe_svg_active = class_exists( 'safe_svg' ) || function_exists( 'safe_svg_current_user_can_upload' );
				
				if ( $safe_svg_active ) {
					$safe_svg_instance = null;
					if ( class_exists( 'safe_svg' ) && isset( $GLOBALS['safe_svg'] ) ) {
						$safe_svg_instance = $GLOBALS['safe_svg'];
					} elseif ( class_exists( 'safe_svg' ) ) {
						$reflection = new \ReflectionClass( 'safe_svg' );
						if ( $reflection->hasMethod( 'get_instance' ) ) {
							$safe_svg_instance = \safe_svg::get_instance();
						}
					}
					
					if ( $safe_svg_instance && method_exists( $safe_svg_instance, 'current_user_can_upload_svg' ) ) {
						$can_upload = $safe_svg_instance->current_user_can_upload_svg();
						if ( ! $can_upload ) {
							$warnings[] = 'SVG mime type (image/svg+xml) is not registered. Safe SVG plugin detected but current user does not have permission to upload SVGs. Check Safe SVG settings (Settings > Safe SVG) to ensure your user role can upload SVGs.';
						} else {
							$warnings[] = 'SVG mime type (image/svg+xml) is not registered in WordPress upload_mimes filter. Safe SVG plugin is active but the mime type is not available in REST API context. This may be a timing issue - try adding this to your theme\'s functions.php: add_filter( \'upload_mimes\', function($mimes) { $mimes[\'svg\'] = \'image/svg+xml\'; return $mimes; }, 999 );';
						}
					} else {
						$warnings[] = 'SVG mime type (image/svg+xml) is not registered in WordPress upload_mimes filter. Safe SVG plugin detected but could not verify permissions.';
					}
				} else {
					$warnings[] = 'SVG mime type (image/svg+xml) is not registered in WordPress upload_mimes filter. Add this to your theme\'s functions.php: add_filter( \'upload_mimes\', function($mimes) { $mimes[\'svg\'] = \'image/svg+xml\'; return $mimes; } );';
				}
			}
		}

		return [
			'allowed'  => empty( $warnings ),
			'warnings' => $warnings,
		];
	}

	/**
	 * Ensure SVG mime type is registered for REST API context.
	 *
	 * Safe SVG and other plugins may only register SVG mime type in admin contexts.
	 * This ensures it's available for REST API calls.
	 *
	 * @return void
	 */
	private function ensure_svg_mime_type_registered(): void {
		$mimes = get_allowed_mime_types();
		if ( isset( $mimes['svg'] ) || isset( $mimes['svgz'] ) ) {
			return;
		}

		if ( class_exists( 'safe_svg' ) ) {
			$safe_svg_instance = null;
			if ( isset( $GLOBALS['safe_svg'] ) ) {
				$safe_svg_instance = $GLOBALS['safe_svg'];
			} else {
				try {
					$reflection = new \ReflectionClass( 'safe_svg' );
					if ( $reflection->hasMethod( 'get_instance' ) ) {
						$safe_svg_instance = \safe_svg::get_instance();
					}
				} catch ( \Exception $e ) {
				}
			}

			if ( $safe_svg_instance && method_exists( $safe_svg_instance, 'current_user_can_upload_svg' ) ) {
				$can_upload = $safe_svg_instance->current_user_can_upload_svg();
				if ( $can_upload && method_exists( $safe_svg_instance, 'allow_svg' ) ) {
					add_filter( 'upload_mimes', [ $safe_svg_instance, 'allow_svg' ], 10, 1 );
					return;
				}
			}
		}

		if ( $this->security_bypass_handler->should_register_svg_mime_type() ) {
			add_filter( 'upload_mimes', [ $this, 'add_svg_mime_type' ], 999, 1 );
		}
	}

	/**
	 * Add SVG mime type to upload_mimes filter.
	 *
	 * @param array $mimes Existing mime types.
	 * @return array Mime types with SVG added.
	 */
	public function add_svg_mime_type( array $mimes ): array {
		$mimes['svg'] = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Check if a URL is an SVG file.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if SVG, false otherwise.
	 */
	private function is_svg_url( string $url ): bool {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! $path ) {
			return false;
		}

		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		return in_array( $extension, [ 'svg', 'svgz' ], true );
	}

	/**
	 * Check if an image already exists by various methods.
	 *
	 * @param string $url The image URL to check.
	 * @return int|null Existing attachment ID if found, null otherwise.
	 */
	private function find_existing_attachment( string $url ): ?int {
		$local_id = Image_Url_Helper::get_local_attachment_id( $url );
		if ( $local_id ) {
			return $local_id;
		}

		global $wpdb;

		$url_hash = sha1( $url );
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_elementor_source_image_hash'
				AND meta_value = %s
				LIMIT 1",
				$url_hash
			)
		);

		if ( $existing_id ) {
			return (int) $existing_id;
		}

		$filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );
		if ( $filename ) {
			$existing_attachments = get_posts( [
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => 1,
				'meta_query'     => [
					[
						'key'     => '_wp_attached_file',
						'value'   => $filename,
						'compare' => 'LIKE',
					],
				],
			] );

			if ( ! empty( $existing_attachments ) ) {
				$attachment_id = $existing_attachments[0]->ID;
				$file_path = get_attached_file( $attachment_id );
				$file_url = wp_get_attachment_url( $attachment_id );

				if ( $file_path && file_exists( $file_path ) ) {
					$response = wp_safe_remote_head( $url );
					if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
						$remote_size = wp_remote_retrieve_header( $response, 'content-length' );
						$local_size = filesize( $file_path );

						if ( $remote_size && (int) $remote_size === $local_size ) {
							return $attachment_id;
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Import an external image URL into WordPress media library.
	 *
	 * @param string $url The image URL to import.
	 * @return array|null Imported image data with 'id' and 'url' keys, or null on failure.
	 */
	public function import_image_url( string $url ): ?array {
		if ( empty( $url ) ) {
			return null;
		}

		$existing_id = $this->find_existing_attachment( $url );
		if ( $existing_id ) {
			return [
				'id'  => $existing_id,
				'url' => wp_get_attachment_url( $existing_id ),
			];
		}

		if ( ! Image_Url_Helper::is_external_url( $url ) ) {
			return null;
		}

		if ( ! class_exists( 'Elementor\Plugin' ) || ! isset( Plugin::$instance ) ) {
			return null;
		}

		if ( ! isset( Plugin::$instance->templates_manager ) ) {
			return null;
		}

		$import_instance = Plugin::$instance->templates_manager->get_import_images_instance();
		if ( ! $import_instance ) {
			return null;
		}

		$import_result = $import_instance->import( [
			'url' => $url,
		] );

		if ( ! $import_result ) {
			return null;
		}

		if ( ! is_array( $import_result ) ) {
			return null;
		}

		if ( ! isset( $import_result['id'] ) ) {
			return null;
		}

		return [
			'id'  => (int) $import_result['id'],
			'url' => $import_result['url'] ?? wp_get_attachment_url( $import_result['id'] ),
		];
	}

	/**
	 * Extract all image URLs from widget settings and styles.
	 *
	 * @param array $widgets Array of widget structures.
	 * @return array Map of original URLs to their locations in widgets.
	 */
	public function extract_image_urls_from_widgets( array $widgets ): array {
		$image_urls = [];

		foreach ( $widgets as $widget_index => $widget ) {
			$this->extract_image_urls_from_widget_recursive( $widget, $widget_index, '', $image_urls );
		}

		return $image_urls;
	}

	/**
	 * Recursively extract image URLs from a widget and its children.
	 *
	 * @param array  $widget      Widget structure.
	 * @param int    $widget_index Index of widget in parent array.
	 * @param string $path        Path to current location (for tracking).
	 * @param array  $image_urls   Reference to array collecting image URLs.
	 */
	private function extract_image_urls_from_widget_recursive( array $widget, int $widget_index, string $path, array &$image_urls ): void {
		$current_path = $path ? $path . '.' . $widget_index : (string) $widget_index;

		if ( isset( $widget['settings'] ) ) {
			$this->extract_image_urls_from_settings( $widget['settings'], $current_path . '.settings', $image_urls );
		}

		if ( isset( $widget['styles'] ) ) {
			$this->extract_image_urls_from_styles( $widget['styles'], $current_path . '.styles', $image_urls );
		}

		if ( isset( $widget['elements'] ) && is_array( $widget['elements'] ) ) {
			foreach ( $widget['elements'] as $child_index => $child_widget ) {
				$this->extract_image_urls_from_widget_recursive( $child_widget, $child_index, $current_path . '.elements', $image_urls );
			}
		}
	}

	/**
	 * Extract image URLs from widget settings.
	 *
	 * @param array  $settings   Settings array.
	 * @param string $path       Path to current location.
	 * @param array  $image_urls Reference to array collecting image URLs.
	 */
	private function extract_image_urls_from_settings( array $settings, string $path, array &$image_urls ): void {
		foreach ( $settings as $key => $value ) {
			$current_path = $path . '.' . $key;

			if ( ! is_array( $value ) ) {
				continue;
			}

			if ( isset( $value['$$type'] ) && 'image-src' === $value['$$type'] ) {
				$url_value = $value['value']['url'] ?? null;
				if ( $url_value && Image_Url_Helper::is_external_url( $url_value ) ) {
					$image_urls[ $url_value ][] = [
						'type' => 'settings',
						'path' => $current_path,
					];
				}
				continue;
			}

			if ( isset( $value['$$type'] ) && 'image' === $value['$$type'] ) {
				$image_value = $value['value'] ?? [];
				if ( isset( $image_value['src'] ) && is_array( $image_value['src'] ) ) {
					$src = $image_value['src'];
					if ( isset( $src['$$type'] ) && 'image-src' === $src['$$type'] ) {
						$src_value = $src['value'] ?? [];
						$url_value = $src_value['url'] ?? null;
						if ( $url_value && Image_Url_Helper::is_external_url( $url_value ) ) {
							$image_urls[ $url_value ][] = [
								'type' => 'settings',
								'path' => $current_path . '.src',
							];
						}
					}
				}
				continue;
			}

			if ( 'image' === $key && is_array( $value ) && isset( $value['value'] ) && is_array( $value['value'] ) && isset( $value['value']['src'] ) ) {
				$image_value = $value['value'];
				$src = $image_value['src'];
				if ( is_array( $src ) && isset( $src['$$type'] ) && 'image-src' === $src['$$type'] ) {
					$src_value = $src['value'] ?? [];
					$url_value = $src_value['url'] ?? null;
					if ( $url_value && Image_Url_Helper::is_external_url( $url_value ) ) {
						$image_urls[ $url_value ][] = [
							'type' => 'settings',
							'path' => $current_path . '.src',
						];
					}
				}
				continue;
			}

			$this->extract_image_urls_from_settings( $value, $current_path, $image_urls );
		}
	}

	/**
	 * Extract image URLs from widget styles (background images).
	 *
	 * @param array  $styles     Styles array.
	 * @param string $path      Path to current location.
	 * @param array  $image_urls Reference to array collecting image URLs.
	 */
	private function extract_image_urls_from_styles( array $styles, string $path, array &$image_urls ): void {
		foreach ( $styles as $breakpoint => $breakpoint_styles ) {
			$breakpoint_path = $path . '.' . $breakpoint;

			if ( is_array( $breakpoint_styles ) ) {
				$this->extract_image_urls_from_style_props( $breakpoint_styles, $breakpoint_path, $image_urls );
			}
		}
	}

	/**
	 * Extract image URLs from style properties (recursively search for image-src).
	 *
	 * @param array  $props      Style properties array.
	 * @param string $path       Path to current location.
	 * @param array  $image_urls Reference to array collecting image URLs.
	 */
	private function extract_image_urls_from_style_props( array $props, string $path, array &$image_urls ): void {
		foreach ( $props as $key => $value ) {
			$current_path = $path . '.' . $key;

			if ( is_array( $value ) ) {
				if ( isset( $value['$$type'] ) && 'image-src' === $value['$$type'] ) {
					$url_value = $value['value']['url'] ?? null;
					if ( $url_value && Image_Url_Helper::is_external_url( $url_value ) ) {
						$image_urls[ $url_value ][] = [
							'type' => 'styles',
							'path' => $current_path,
						];
					}
				} elseif ( isset( $value['$$type'] ) && 'background' === $value['$$type'] ) {
					$this->extract_image_urls_from_background_prop( $value, $current_path, $image_urls );
				} else {
					$this->extract_image_urls_from_style_props( $value, $current_path, $image_urls );
				}
			}
		}
	}

	/**
	 * Extract image URLs from background prop structure.
	 *
	 * @param array  $background Background prop structure.
	 * @param string $path      Path to current location.
	 * @param array  $image_urls Reference to array collecting image URLs.
	 */
	private function extract_image_urls_from_background_prop( array $background, string $path, array &$image_urls ): void {
		$value = $background['value'] ?? [];

		if ( isset( $value['background-overlay'] ) ) {
			$overlay = $value['background-overlay'];
			$overlay_value = $overlay['value'] ?? [];

			if ( isset( $overlay_value['image'] ) ) {
				$image = $overlay_value['image'];
				$image_value = $image['value'] ?? [];

				if ( isset( $image_value['src'] ) ) {
					$src = $image_value['src'];
					$src_value = $src['value'] ?? [];

					$url_value = $src_value['url'] ?? null;
					if ( $url_value && Image_Url_Helper::is_external_url( $url_value ) ) {
						$image_urls[ $url_value ][] = [
							'type' => 'styles',
							'path' => $path,
						];
					}
				}
			}
		}
	}

	/**
	 * Update image props in widgets with imported attachment IDs.
	 *
	 * @param array $widgets    Array of widget structures.
	 * @param array $image_map  Map of original URLs to imported attachment data.
	 * @return array Updated widgets.
	 */
	public function update_widget_image_props( array $widgets, array $image_map ): array {
		$updated_widgets = [];

		foreach ( $widgets as $widget ) {
			$updated_widgets[] = $this->update_widget_image_props_recursive( $widget, $image_map );
		}

		return $updated_widgets;
	}

	/**
	 * Recursively update image props in a widget.
	 *
	 * @param array $widget    Widget structure.
	 * @param array $image_map Map of original URLs to imported attachment data.
	 * @return array Updated widget.
	 */
	private function update_widget_image_props_recursive( array $widget, array $image_map ): array {
		if ( isset( $widget['settings'] ) ) {
			$widget['settings'] = $this->update_image_props_in_array( $widget['settings'], $image_map );
		}

		if ( isset( $widget['styles'] ) ) {
			$widget['styles'] = $this->update_image_props_in_styles( $widget['styles'], $image_map );
		}

		if ( isset( $widget['elements'] ) && is_array( $widget['elements'] ) ) {
			$widget['elements'] = array_map(
				function( $child ) use ( $image_map ) {
					return $this->update_widget_image_props_recursive( $child, $image_map );
				},
				$widget['elements']
			);
		}

		return $widget;
	}

	/**
	 * Update image props in a settings array.
	 *
	 * @param array $array     Settings array.
	 * @param array $image_map Map of original URLs to imported attachment data.
	 * @return array Updated array.
	 */
	private function update_image_props_in_array( array $array, array $image_map ): array {
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['$$type'] ) && 'image-src' === $value['$$type'] ) {
					$url_value = $value['value']['url'] ?? null;
					if ( $url_value && isset( $image_map[ $url_value ] ) ) {
						$imported = $image_map[ $url_value ];
						$array[ $key ] = [
							'$$type' => 'image-src',
							'value'  => [
								'id'  => Image_Attachment_Id_Prop_Type::generate( $imported['id'] ),
								'url' => null,
							],
						];
					}
				} elseif ( ( isset( $value['$$type'] ) && 'image' === $value['$$type'] ) || ( 'image' === $key && is_array( $value ) ) ) {
					$image_value = $value['value'] ?? [];
					if ( isset( $image_value['src'] ) ) {
						$src = $image_value['src'];
						if ( isset( $src['$$type'] ) && 'image-src' === $src['$$type'] ) {
							$src_value = $src['value'] ?? [];
							$url_value = $src_value['url'] ?? null;
							if ( $url_value && isset( $image_map[ $url_value ] ) ) {
								$imported = $image_map[ $url_value ];
								$image_value['src'] = [
									'$$type' => 'image-src',
									'value'  => [
										'id'  => Image_Attachment_Id_Prop_Type::generate( $imported['id'] ),
										'url' => null,
									],
								];
								$array[ $key ] = array_merge( $value, [
									'value' => $image_value,
								] );
							} else {
								$array[ $key ] = $this->update_image_props_in_array( $value, $image_map );
							}
						} else {
							$array[ $key ] = $this->update_image_props_in_array( $value, $image_map );
						}
					} else {
						$array[ $key ] = $this->update_image_props_in_array( $value, $image_map );
					}
				} else {
					$array[ $key ] = $this->update_image_props_in_array( $value, $image_map );
				}
			}
		}

		return $array;
	}

	/**
	 * Update image props in styles array.
	 *
	 * @param array $styles    Styles array.
	 * @param array $image_map Map of original URLs to imported attachment data.
	 * @return array Updated styles.
	 */
	private function update_image_props_in_styles( array $styles, array $image_map ): array {
		foreach ( $styles as $breakpoint => $breakpoint_styles ) {
			if ( is_array( $breakpoint_styles ) ) {
				$styles[ $breakpoint ] = $this->update_image_props_in_style_props( $breakpoint_styles, $image_map );
			}
		}

		return $styles;
	}

	/**
	 * Update image props in style properties.
	 *
	 * @param array $props     Style properties array.
	 * @param array $image_map Map of original URLs to imported attachment data.
	 * @return array Updated props.
	 */
	private function update_image_props_in_style_props( array $props, array $image_map ): array {
		foreach ( $props as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['$$type'] ) && 'image-src' === $value['$$type'] ) {
					$url_value = $value['value']['url'] ?? null;
					if ( $url_value && isset( $image_map[ $url_value ] ) ) {
						$imported = $image_map[ $url_value ];
						$props[ $key ] = [
							'$$type' => 'image-src',
							'value'  => [
								'id'  => Image_Attachment_Id_Prop_Type::generate( $imported['id'] ),
								'url' => null,
							],
						];
					}
				} elseif ( isset( $value['$$type'] ) && 'background' === $value['$$type'] ) {
					$props[ $key ] = $this->update_background_prop( $value, $image_map );
				} else {
					$props[ $key ] = $this->update_image_props_in_style_props( $value, $image_map );
				}
			}
		}

		return $props;
	}

	/**
	 * Update image URL in background prop structure.
	 *
	 * @param array $background Background prop structure.
	 * @param array $image_map  Map of original URLs to imported attachment data.
	 * @return array Updated background prop.
	 */
	private function update_background_prop( array $background, array $image_map ): array {
		$value = $background['value'] ?? [];

		if ( isset( $value['background-overlay'] ) ) {
			$overlay = $value['background-overlay'];
			$overlay_value = $overlay['value'] ?? [];

			if ( isset( $overlay_value['image'] ) ) {
				$image = $overlay_value['image'];
				$image_value = $image['value'] ?? [];

				if ( isset( $image_value['src'] ) ) {
					$src = $image_value['src'];
					$src_value = $src['value'] ?? [];

					$url_value = $src_value['url'] ?? null;
					if ( $url_value && isset( $image_map[ $url_value ] ) ) {
						$imported = $image_map[ $url_value ];
						$image_value['src'] = [
							'$$type' => 'image-src',
							'value'  => [
								'id'  => Image_Attachment_Id_Prop_Type::generate( $imported['id'] ),
								'url' => null,
							],
						];
						$overlay_value['image']['value'] = $image_value;
						$value['background-overlay']['value'] = $overlay_value;
						$background['value'] = $value;
					}
				}
			}
		}

		return $background;
	}

	/**
	 * Find existing SVG attachment by content hash.
	 *
	 * @param string $svg_content SVG content to check.
	 * @return int|null Existing attachment ID if found, null otherwise.
	 */
	private function find_existing_svg_by_content( string $svg_content ): ?int {
		$content_hash = md5( $svg_content );
		
		global $wpdb;
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_elementor_inline_svg_hash'
				AND meta_value = %s
				LIMIT 1",
				$content_hash
			)
		);
		
		if ( $existing_id ) {
			return (int) $existing_id;
		}
		
		return null;
	}

	/**
	 * Import inline SVG content into WordPress media library.
	 *
	 * @param string      $svg_content SVG content to import.
	 * @param string|null  $filename    Optional filename. If not provided, generated from content hash.
	 * @return array|null Imported image data with 'id' and 'url' keys, or null on failure.
	 */
	public function import_inline_svg( string $svg_content, ?string $filename = null ): array {
		if ( empty( trim( $svg_content ) ) ) {
			return [
				'success' => false,
				'id' => null,
				'url' => null,
				'warnings' => [ 'SVG content is empty' ],
			];
		}

		$svg_content = $this->strip_dangerous_svg_elements( $svg_content );

		$permission_check = $this->check_svg_import_permissions();
		if ( ! $permission_check['allowed'] ) {
			return [
				'success' => false,
				'id' => null,
				'url' => null,
				'warnings' => $permission_check['warnings'],
			];
		}

		$existing_id = $this->find_existing_svg_by_content( $svg_content );
		if ( $existing_id ) {
			return [
				'success' => true,
				'id'  => $existing_id,
				'url' => wp_get_attachment_url( $existing_id ),
				'warnings' => [],
			];
		}

		$svg_instance = new Svg();
		$sanitized_svg = $svg_instance->sanitizer( $svg_content );
		if ( false === $sanitized_svg ) {
			return [
				'success' => false,
				'id' => null,
				'url' => null,
				'warnings' => [ 'SVG sanitization failed' ],
			];
		}

		if ( null === $filename ) {
			$content_hash = md5( $svg_content );
			$filename = 'svg-' . substr( $content_hash, 0, 8 ) . '.svg';
		}

		$upload_dir = wp_upload_dir();
		if ( $upload_dir['error'] ) {
			return [
				'success' => false,
				'id' => null,
				'url' => null,
				'warnings' => [ 'WordPress upload directory error: ' . $upload_dir['error'] ],
			];
		}

		$file_path = $upload_dir['path'] . '/' . $filename;
		$file_url = $upload_dir['url'] . '/' . $filename;

		$unique_filename = wp_unique_filename( $upload_dir['path'], $filename );
		$file_path = $upload_dir['path'] . '/' . $unique_filename;
		$file_url = $upload_dir['url'] . '/' . $unique_filename;

		$upload_result = wp_upload_bits( $unique_filename, null, $sanitized_svg );
		if ( $upload_result['error'] ) {
			return [
				'success' => false,
				'id' => null,
				'url' => null,
				'warnings' => [ 'File upload failed: ' . $upload_result['error'] ],
			];
		}

		$attachment_data = [
			'post_mime_type' => 'image/svg+xml',
			'post_title'     => sanitize_file_name( pathinfo( $unique_filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment_data, $upload_result['file'] );
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			$error_message = is_wp_error( $attachment_id ) ? $attachment_id->get_error_message() : 'unknown error';
			return [
				'success' => false,
				'id' => null,
				'url' => null,
				'warnings' => [ 'Attachment creation failed: ' . $error_message ],
			];
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $upload_result['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

		$content_hash = md5( $svg_content );
		update_post_meta( $attachment_id, '_elementor_inline_svg_hash', $content_hash );

		$result = [
			'success' => true,
			'id'  => $attachment_id,
			'url' => wp_get_attachment_url( $attachment_id ),
			'warnings' => [],
		];
		return $result;
	}

	/**
	 * Import all external images found in widgets.
	 *
	 * @param array $widgets Array of widget structures.
	 * @return array Updated widgets with imported images and warnings.
	 */
	public function import_images_in_widgets( array $widgets ): array {
		$image_urls = $this->extract_image_urls_from_widgets( $widgets );
		$image_map  = [];
		$imported   = [];
		$warnings   = [];

		if ( empty( $image_urls ) ) {
			return [
				'widgets'  => $widgets,
				'imported' => [],
				'warnings'  => [],
			];
		}

		$has_svg = false;
		foreach ( array_keys( $image_urls ) as $url ) {
			if ( $this->is_svg_url( $url ) ) {
				$has_svg = true;
				break;
			}
		}

		if ( $has_svg ) {
			$permission_check = $this->check_svg_import_permissions();
			if ( ! $permission_check['allowed'] ) {
				$warnings = array_merge( $warnings, $permission_check['warnings'] );
			}
		}

		foreach ( array_keys( $image_urls ) as $url ) {
			$imported_result = $this->import_image_url( $url );
			if ( $imported_result && isset( $imported_result['id'] ) ) {
				$image_map[ $url ] = $imported_result;
				$imported[] = [
					'url' => $url,
					'id'  => $imported_result['id'],
				];
			} elseif ( $this->is_svg_url( $url ) && empty( $warnings ) ) {
				$warnings[] = sprintf( 'Failed to import SVG: %s. Please check SVG import permissions.', $url );
			}
		}

		if ( empty( $image_map ) ) {
			return [
				'widgets'  => $widgets,
				'imported' => [],
				'warnings' => $warnings,
			];
		}

		$updated_widgets = $this->update_widget_image_props( $widgets, $image_map );

		return [
			'widgets'  => $updated_widgets,
			'imported' => $imported,
			'warnings' => $warnings,
		];
	}

	/**
	 * Strip script tags, dangerous elements, event handlers, and javascript: URLs from SVG content.
	 *
	 * @param string $svg_content Raw SVG content.
	 * @return string Sanitized SVG content.
	 */
	private function strip_dangerous_svg_elements( string $svg_content ): string {
		$dangerous_tags_pattern = '/<\s*\/?\s*(script|foreignObject|iframe|object|embed|applet|form|input|textarea|button|select)\b[^>]*>.*?<\s*\/\s*\1\s*>|<\s*\/?\s*(script|foreignObject|iframe|object|embed|applet|form|input|textarea|button|select)\b[^>]*\/?>/is';
		$event_handler_pattern = '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i';
		$javascript_href_pattern = '/(xlink:href|href)\s*=\s*["\']javascript:[^"\']*["\']/i';

		$sanitized = preg_replace( $dangerous_tags_pattern, '', $svg_content );
		$sanitized = preg_replace( $event_handler_pattern, '', $sanitized );
		$sanitized = preg_replace( $javascript_href_pattern, '', $sanitized );

		return $sanitized;
	}
}
