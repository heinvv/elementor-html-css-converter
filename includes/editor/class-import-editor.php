<?php
/**
 * Import Editor Integration
 *
 * Handles editor UI integration for the import functionality.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Editor {

	public function __construct() {
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'elementor/editor/v2/scripts/enqueue', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {
		$plugin_url  = plugin_dir_url( EHCC_FILE );
		$script_path = $plugin_url . 'assets/js/editor/compiled/import-modal-react.js';
		$compiled_file = dirname( EHCC_FILE ) . '/assets/js/editor/compiled/import-modal-react.js';
		$script_ver   = file_exists( $compiled_file ) ? (string) filemtime( $compiled_file ) : EHCC_VERSION;

		wp_enqueue_script(
			'ehcc-import-button',
			$plugin_url . 'assets/js/editor/import-button.js',
			[ 'jquery', 'elementor-common' ],
			$script_ver,
			true
		);

		wp_enqueue_script(
			'ehcc-import-modal-react',
			$script_path,
			[ 'elementor-common' ],
			$script_ver,
			true
		);

		$api_url = rest_url( 'html-css-converter/v1/' );
		$localize_data = [
			'apiUrl' => $api_url,
			'nonce'  => wp_create_nonce( 'wp_rest' ),
		];

		wp_localize_script(
			'ehcc-import-modal-react',
			'ehccImport',
			$localize_data
		);
	}
}
