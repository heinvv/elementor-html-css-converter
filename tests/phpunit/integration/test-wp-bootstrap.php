<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use PHPUnit\Framework\TestCase;

class Test_Wp_Bootstrap extends TestCase {

	public function test_wordpress_loaded(): void {
		$this->assertTrue( defined( 'ABSPATH' ) );
		$this->assertTrue( function_exists( 'get_bloginfo' ) );
	}

	public function test_elementor_loaded(): void {
		$this->assertTrue( class_exists( '\Elementor\Plugin' ) );
		$this->assertNotNull( \Elementor\Plugin::$instance );
	}

	public function test_plugin_loaded(): void {
		$this->assertTrue( defined( 'EHCC_PATH' ) );
		$this->assertTrue( class_exists( '\ElementorHtmlCssConverter\Plugin' ) );
	}

	public function test_convert_html_returns_response(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', '<div>test</div>' );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'Status: ' . $response->get_status() . ', Data: ' . wp_json_encode( $data ) );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data, 'Keys: ' . implode( ', ', array_keys( $data ) ) );
		$this->assertTrue( $data['success'], 'success=false, response: ' . wp_json_encode( $data ) );
	}

}
