<?php
/**
 * Import Template Post Type
 *
 * Custom post type for HTML/CSS converter import modal data.
 * Not accessible to non-logged-in users.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Template_Post_Type {

	private const POST_TYPE = 'ehcc_import_template';

	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ], 5 );
	}

	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'               => [
					'name'          => 'EHCC Import Templates',
					'singular_name'  => 'Import Template',
					'all_items'      => 'All Import Templates',
					'edit_item'      => 'Edit Import Template',
					'view_item'      => 'View Import Template',
					'add_new_item'   => 'Add New',
					'search_items'   => 'Search Import Templates',
				],
				'public'               => false,
				'publicly_queryable'   => false,
				'show_ui'              => true,
				'show_in_menu'         => 'edit.php?post_type=elementor_library',
				'show_in_nav_menus'   => false,
				'show_in_rest'         => false,
				'capability_type'      => 'post',
				'map_meta_cap'         => true,
				'supports'             => [ 'title', 'custom-fields' ],
			]
		);
	}
}

