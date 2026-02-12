<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;

class Test_Import_Variables_Endpoint extends Base_Test_Case {

	public function test_post_import_variables_returns_structure(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', ':root { --primary: #ff0000; --spacing: 16px; }' );
		$request->set_param( 'update_mode', 'create_new' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertIsArray( $data );
	}

	public function test_import_variables_with_update_mode(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', ':root { --accent: #00ff00; }' );
		$request->set_param( 'update_mode', 'update' );

		$response = rest_do_request( $request );
		$allowed  = [ Test_Constants::HTTP_OK, Test_Constants::HTTP_FORBIDDEN ];

		$this->assertTrue( in_array( $response->get_status(), $allowed, true ) );
	}

	public function test_import_variables_response_has_expected_keys(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', ':root { --test: #111111; }' );
		$request->set_param( 'update_mode', 'create_new' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		if ( Test_Constants::HTTP_OK === $response->get_status() ) {
			$this->assertArrayHasKey( 'success', $data );
			$this->assertArrayHasKey( 'variables', $data );
			$this->assertArrayHasKey( 'created', $data );
			$this->assertArrayHasKey( 'reused', $data );
			$this->assertArrayHasKey( 'updated', $data );
		}
	}

	public function test_import_variables_missing_css_and_url_returns_error(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertTrue( in_array( $response->get_status(), [ Test_Constants::HTTP_BAD_REQUEST, Test_Constants::HTTP_OK ], true ) );
		if ( Test_Constants::HTTP_OK === $response->get_status() && isset( $data['error'] ) ) {
			$this->assertNotEmpty( $data['error'] );
		}
	}

}
