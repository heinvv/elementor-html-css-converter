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
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_styles' ] );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[EHCC Import Editor] Constructor called' );
		}
	}

	public function enqueue_scripts() {
		$plugin_url = plugin_dir_url( EHCC_FILE );
		$button_url = $plugin_url . 'assets/js/editor/import-button.js';
		$modal_url = $plugin_url . 'assets/js/editor/import-modal.js';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[EHCC Import Editor] Enqueuing scripts' );
			error_log( '[EHCC Import Editor] Button URL: ' . $button_url );
			error_log( '[EHCC Import Editor] Modal URL: ' . $modal_url );
			error_log( '[EHCC Import Editor] Plugin URL: ' . $plugin_url );
		}

		wp_enqueue_script(
			'ehcc-import-button',
			$button_url,
			[ 'jquery', 'elementor-common' ],
			EHCC_VERSION,
			true
		);

		wp_enqueue_script(
			'ehcc-import-modal-react',
			$plugin_url . 'assets/js/editor/import-modal-react.js',
			[ 'elementor-common' ],
			EHCC_VERSION,
			true
		);

		$api_url = rest_url( 'html-css-converter/v1/' );
		$localize_data = [
			'apiUrl' => $api_url,
			'nonce'  => wp_create_nonce( 'wp_rest' ),
		];

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[EHCC Import Editor] Localizing script with API URL: ' . $api_url );
		}

		wp_localize_script(
			'ehcc-import-modal',
			'ehccImport',
			$localize_data
		);
	}

	public function enqueue_styles() {
		$plugin_url = plugin_dir_url( EHCC_FILE );
		$css_url = $plugin_url . 'assets/css/editor/import-editor.css';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[EHCC Import Editor] Enqueuing styles' );
			error_log( '[EHCC Import Editor] CSS URL: ' . $css_url );
		}

		wp_enqueue_style(
			'ehcc-import-editor',
			$css_url,
			[ 'elementor-editor' ],
			EHCC_VERSION
		);
	}
}
