<?php
/**
 * Elementor Document Service Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Elementor_Document_Service
 *
 * Wraps Elementor document operations for reading and writing widget data.
 */
class Elementor_Document_Service {
	/**
	 * Get an Elementor document by post ID.
	 *
	 * @param int $post_id The post ID.
	 * @return \Elementor\Core\Base\Document|null The document or null.
	 */
	public function get_document( int $post_id ) {
		if ( ! $this->is_elementor_available() ) {
			return null;
		}

		return \Elementor\Plugin::$instance->documents->get( $post_id );
	}

	/**
	 * Find a widget in a post by widget ID.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $widget_id The widget ID.
	 * @return array|false The widget data or false if not found.
	 */
	public function find_widget( int $post_id, string $widget_id ) {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return false;
		}

		$elements = $document->get_elements_data();

		if ( empty( $elements ) ) {
			return false;
		}

		return \Elementor\Utils::find_element_recursive( $elements, $widget_id );
	}

	/**
	 * Update a widget in a post and save.
	 *
	 * @param int      $post_id        The post ID.
	 * @param string   $widget_id      The widget ID.
	 * @param callable $update_callback Callback that receives widget data and returns modified data.
	 * @return bool True on success, false on failure.
	 */
	public function update_widget( int $post_id, string $widget_id, callable $update_callback ): bool {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return false;
		}

		$elements = $document->get_elements_data();

		if ( empty( $elements ) ) {
			return false;
		}

		$updated_elements = $this->iterate_and_update( $elements, $widget_id, $update_callback );

		return $this->save_elements( $document, $updated_elements );
	}

	/**
	 * Create a new Elementor post.
	 *
	 * @param string $title       The post title.
	 * @param string $post_status The post status (draft, publish, etc.).
	 * @param string $post_type   The post type (page, post).
	 * @return array|false Array with post_id and document, or false on failure.
	 */
	public function create_post( string $title, string $post_status = 'draft', string $post_type = 'page' ) {
		if ( ! $this->is_elementor_available() ) {
			return false;
		}

		$document_type = 'wp-' . $post_type;

		$document = \Elementor\Plugin::$instance->documents->create(
			$document_type,
			[
				'post_title'  => $title,
				'post_status' => $post_status,
				'post_type'   => $post_type,
			]
		);

		if ( is_wp_error( $document ) ) {
			return false;
		}

		return [
			'post_id'  => $document->get_main_id(),
			'document' => $document,
		];
	}

	/**
	 * Add multiple widgets to a specific container in a post.
	 *
	 * @param int    $post_id      The post ID.
	 * @param string $container_id The container widget ID to insert into.
	 * @param array  $widgets      Array of widget data to add.
	 * @return array|false Array of new widget IDs or false on failure.
	 */
	public function add_widgets_to_container( int $post_id, string $container_id, array $widgets ) {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return false;
		}

		$elements = $document->get_elements_data();

		if ( empty( $elements ) ) {
			return false;
		}

		$widget_ids = [];
		foreach ( $widgets as &$widget ) {
			$widget_id         = $this->generate_element_id();
			$widget['id']      = $widget_id;
			$widget_ids[]      = $widget_id;

			if ( ! empty( $widget['elements'] ) ) {
				$widget['elements'] = $this->assign_ids_recursively( $widget['elements'] );
			}
		}
		unset( $widget );

		$updated_elements = $this->add_widgets_to_container_recursive( $elements, $container_id, $widgets );

		if ( ! $this->save_elements( $document, $updated_elements ) ) {
			return false;
		}

		return $widget_ids;
	}

	/**
	 * Recursively assign IDs to widget elements.
	 *
	 * @param array $elements The elements to process.
	 * @return array The elements with IDs assigned.
	 */
	protected function assign_ids_recursively( array $elements ): array {
		foreach ( $elements as &$element ) {
			if ( ! isset( $element['id'] ) || empty( $element['id'] ) ) {
				$element['id'] = $this->generate_element_id();
			}

			if ( ! empty( $element['elements'] ) ) {
				$element['elements'] = $this->assign_ids_recursively( $element['elements'] );
			}
		}
		unset( $element );

		return $elements;
	}

	/**
	 * Recursively find container and add widgets to it.
	 *
	 * @param array  $elements     The elements array.
	 * @param string $container_id The container ID to find.
	 * @param array  $widgets      The widgets to add.
	 * @return array The updated elements.
	 */
	private function add_widgets_to_container_recursive( array $elements, string $container_id, array $widgets ): array {
		foreach ( $elements as $index => $element ) {
			if ( isset( $element['id'] ) && $element['id'] === $container_id ) {
				if ( ! isset( $elements[ $index ]['elements'] ) ) {
					$elements[ $index ]['elements'] = [];
				}
				$elements[ $index ]['elements'] = array_merge( $elements[ $index ]['elements'], $widgets );
				return $elements;
			}

			if ( ! empty( $element['elements'] ) ) {
				$elements[ $index ]['elements'] = $this->add_widgets_to_container_recursive(
					$element['elements'],
					$container_id,
					$widgets
				);
			}
		}

		return $elements;
	}

	/**
	 * Add multiple widgets to the root level of a post.
	 *
	 * All widgets are added directly to the root without wrapping.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $widgets Array of widget data to add.
	 * @return array|false Array with container_id and widget_ids, or false on failure.
	 */
	public function add_widgets_to_root( int $post_id, array $widgets ) {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return false;
		}

		$elements = $document->get_elements_data();

		$widget_ids = [];
		foreach ( $widgets as &$widget ) {
			$widget_id    = $this->generate_element_id();
			$widget['id'] = $widget_id;
			$widget_ids[] = $widget_id;

			if ( ! empty( $widget['elements'] ) ) {
				$widget['elements'] = $this->assign_ids_recursively( $widget['elements'] );
			}
		}
		unset( $widget );

		$elements = array_merge( $elements, $widgets );

		if ( ! $this->save_elements( $document, $elements ) ) {
			return false;
		}

		return [
			'container_id' => null,
			'widget_ids'   => $widget_ids,
		];
	}

	/**
	 * Check if widgets need a wrapper container.
	 *
	 * Widgets need a wrapper only if ANY widget is a non-container type.
	 * If all widgets are containers, they are added directly to root.
	 *
	 * @param array $widgets Array of widgets.
	 * @return bool True if wrapper is needed.
	 */
	private function widgets_need_wrapper( array $widgets ): bool {
		foreach ( $widgets as $widget ) {
			if ( ! $this->is_container_element( $widget ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a widget is a container element.
	 *
	 * @param array $widget Widget data.
	 * @return bool True if container.
	 */
	private function is_container_element( array $widget ): bool {
		$container_types = [ 'e-div-block', 'e-flexbox', 'container' ];

		$el_type = $widget['elType'] ?? '';
		if ( in_array( $el_type, $container_types, true ) ) {
			return true;
		}

		$widget_type = $widget['widgetType'] ?? '';
		if ( in_array( $widget_type, $container_types, true ) ) {
			return true;
		}

		if ( ! empty( $widget['elements'] ) && is_array( $widget['elements'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add a widget to a post.
	 *
	 * @param int   $post_id     The post ID.
	 * @param array $widget_data The widget data to add.
	 * @return string|false The new widget ID or false on failure.
	 */
	public function add_widget( int $post_id, array $widget_data ) {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return false;
		}

		$elements = $document->get_elements_data();

		$widget_id = $this->generate_element_id();
		$widget_data['id'] = $widget_id;

		if ( ! isset( $widget_data['elType'] ) ) {
			$widget_data['elType'] = 'widget';
		}

		$elements = $this->add_widget_to_elements( $elements, $widget_data );

		if ( ! $this->save_elements( $document, $elements ) ) {
			return false;
		}

		return $widget_id;
	}

	/**
	 * Save a post with initial widget structure.
	 *
	 * @param int   $post_id     The post ID.
	 * @param array $widget_data The widget data.
	 * @return bool True on success.
	 */
	public function save_with_widget( int $post_id, array $widget_data ): bool {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return false;
		}

		$elements = $this->create_container_with_widget( $widget_data );

		return $this->save_elements( $document, $elements );
	}

	/**
	 * Get the edit URL for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return string The edit URL.
	 */
	public function get_edit_url( int $post_id ): string {
		$document = $this->get_document( $post_id );

		if ( $document ) {
			return $document->get_edit_url();
		}

		return admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
	}

	/**
	 * Save widgets as an Elementor template in the library.
	 *
	 * Creates an elementor_library post directly, bypassing permission checks
	 * for REST API requests without proper authentication.
	 *
	 * @param string $title   The template title.
	 * @param array  $widgets Array of widget data to save.
	 * @return int|false Template ID on success, false on failure.
	 */
	public function save_as_template( string $title, array $widgets ): int|false {
		if ( ! $this->is_elementor_available() ) {
			return false;
		}

		$template_id = wp_insert_post( [
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_type'   => 'elementor_library',
		] );

		if ( is_wp_error( $template_id ) || ! $template_id ) {
			return false;
		}

		foreach ( $widgets as &$widget ) {
			$widget['id'] = $this->generate_element_id();
			if ( ! empty( $widget['elements'] ) ) {
				$widget['elements'] = $this->assign_ids_recursively( $widget['elements'] );
			}
		}
		unset( $widget );

		$editor_data = $this->get_elements_raw_data( $widgets );
		$json_value  = wp_slash( wp_json_encode( $editor_data ) );

		if ( false === $json_value ) {
			wp_delete_post( $template_id, true );
			return false;
		}

		update_metadata( 'post', $template_id, '_elementor_data', $json_value );
		update_post_meta( $template_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $template_id, '_elementor_template_type', 'page' );
		update_post_meta( $template_id, '_elementor_version', ELEMENTOR_VERSION );

		wp_set_object_terms( $template_id, 'page', 'elementor_library_type' );

		return $template_id;
	}

	/**
	 * Generate a unique element ID.
	 *
	 * @return string The generated ID.
	 */
	public function generate_element_id(): string {
		if ( $this->is_elementor_available() && method_exists( '\Elementor\Utils', 'generate_random_string' ) ) {
			return \Elementor\Utils::generate_random_string();
		}

		return substr( md5( uniqid( '', true ) ), 0, 7 );
	}

	/**
	 * Check if Elementor is available.
	 *
	 * @return bool True if Elementor is loaded.
	 */
	private function is_elementor_available(): bool {
		return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Iterate through elements and update matching widget.
	 *
	 * @param array    $elements        The elements array.
	 * @param string   $widget_id       The widget ID to find.
	 * @param callable $update_callback The update callback.
	 * @return array The updated elements.
	 */
	private function iterate_and_update( array $elements, string $widget_id, callable $update_callback ): array {
		foreach ( $elements as $index => $element ) {
			if ( isset( $element['id'] ) && $element['id'] === $widget_id ) {
				$elements[ $index ] = $update_callback( $element );
			}

			if ( ! empty( $element['elements'] ) ) {
				$elements[ $index ]['elements'] = $this->iterate_and_update(
					$element['elements'],
					$widget_id,
					$update_callback
				);
			}
		}

		return $elements;
	}

	/**
	 * Save elements to document.
	 *
	 * Uses direct post meta update to bypass Elementor's permission checks
	 * which would fail for REST API requests without proper authentication.
	 * Matches the approach from css-converter PR.
	 *
	 * @param \Elementor\Core\Base\Document $document The document.
	 * @param array                         $elements The elements to save.
	 * @return bool True on success.
	 */
	private function save_elements( $document, array $elements ): bool {
		$post_id = $document->get_main_id();

		$editor_data = $this->get_elements_raw_data( $elements );

		$json_value = wp_slash( wp_json_encode( $editor_data ) );

		if ( false === $json_value ) {
			return false;
		}

		update_metadata( 'post', $post_id, '_elementor_data', $json_value );

		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'wp-post' );
		update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );

		$document->set_is_built_with_elementor( true );

		delete_post_meta( $post_id, '_elementor_element_cache' );
		delete_post_meta( $post_id, '_elementor_css' );
		delete_post_meta( $post_id, '_elementor_atomic_cache_validity' );

		do_action( 'elementor/atomic-widgets/styles/clear', [ 'local', $post_id ] );
		do_action( 'elementor/atomic-widgets/styles/clear', [ 'local', $post_id, 'frontend' ] );
		do_action( 'elementor/atomic-widgets/styles/clear', [ 'local', $post_id, 'preview' ] );

		return true;
	}

	/**
	 * Get raw data from elements for saving.
	 *
	 * This processes the elements through Elementor's data handling.
	 *
	 * @param array $elements The elements.
	 * @return array The processed elements.
	 */
	protected function get_elements_raw_data( array $elements ): array {
		$editor_data = [];

		foreach ( $elements as $element_data ) {
			$editor_data[] = $this->get_element_raw_data( $element_data );
		}

		return $editor_data;
	}

	/**
	 * Get raw data for a single element.
	 *
	 * @param array $element_data The element data.
	 * @return array The processed element data.
	 */
	private function get_element_raw_data( array $element_data ): array {
		$data = [
			'id'       => $element_data['id'] ?? '',
			'elType'   => $element_data['elType'] ?? 'widget',
			'settings' => $element_data['settings'] ?? [],
			'elements' => [],
		];

		if ( isset( $element_data['widgetType'] ) ) {
			$data['widgetType'] = $element_data['widgetType'];
		}

		if ( isset( $element_data['styles'] ) ) {
			$data['styles'] = $element_data['styles'];
		}

		if ( isset( $element_data['interactions'] ) ) {
			$data['interactions'] = $element_data['interactions'];
		}

		if ( isset( $element_data['editor_settings'] ) ) {
			$data['editor_settings'] = $element_data['editor_settings'];
		}

		if ( isset( $element_data['version'] ) ) {
			$data['version'] = $element_data['version'];
		}

		if ( isset( $element_data['isInner'] ) ) {
			$data['isInner'] = $element_data['isInner'];
		}

		if ( ! empty( $element_data['elements'] ) ) {
			foreach ( $element_data['elements'] as $child ) {
				$data['elements'][] = $this->get_element_raw_data( $child );
			}
		}

		return $data;
	}

	/**
	 * Add widget to elements structure.
	 *
	 * @param array $elements    The existing elements.
	 * @param array $widget_data The widget to add.
	 * @return array The updated elements.
	 */
	private function add_widget_to_elements( array $elements, array $widget_data ): array {
		if ( empty( $elements ) ) {
			return $this->create_container_with_widget( $widget_data );
		}

		$container_found = $this->find_first_container( $elements );

		if ( $container_found ) {
			return $this->add_to_container( $elements, $widget_data );
		}

		return $this->create_container_with_widget( $widget_data );
	}

	/**
	 * Find first div-block or flexbox container in elements (v4 atomic pattern).
	 *
	 * @param array $elements The elements.
	 * @return bool True if container found.
	 */
	private function find_first_container( array $elements ): bool {
		foreach ( $elements as $element ) {
			$widget_type = $element['widgetType'] ?? '';

			if ( in_array( $widget_type, [ 'e-div-block', 'e-flexbox' ], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add widget to first available div-block or flexbox (v4 atomic pattern).
	 *
	 * @param array $elements    The elements.
	 * @param array $widget_data The widget data.
	 * @return array The updated elements.
	 */
	private function add_to_container( array $elements, array $widget_data ): array {
		foreach ( $elements as $index => $element ) {
			$widget_type = $element['widgetType'] ?? '';

			if ( in_array( $widget_type, [ 'e-div-block', 'e-flexbox' ], true ) ) {
				if ( ! isset( $elements[ $index ]['elements'] ) ) {
					$elements[ $index ]['elements'] = [];
				}
				$elements[ $index ]['elements'][] = $widget_data;
				return $elements;
			}

			if ( ! empty( $element['elements'] ) ) {
				$elements[ $index ]['elements'] = $this->add_to_container( $element['elements'], $widget_data );
				return $elements;
			}
		}

		return $elements;
	}

	/**
	 * Create a div-block wrapper with widget (v4 atomic pattern).
	 *
	 * @param array $widget_data The widget data.
	 * @return array The div-block structure with widget.
	 */
	private function create_container_with_widget( array $widget_data ): array {
		return [
			[
				'id'         => $this->generate_element_id(),
				'elType'     => 'widget',
				'widgetType' => 'e-div-block',
				'settings'   => [],
				'styles'     => [],
				'elements'   => [ $widget_data ],
			],
		];
	}
}
