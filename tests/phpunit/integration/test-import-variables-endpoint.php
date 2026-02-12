<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use PHPUnit\Framework\TestCase;

class Test_Import_Variables_Endpoint extends TestCase {

	public function test_post_import_variables_returns_structure(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', ':root { --primary: #ff0000; --spacing: 16px; }' );
		$request->set_param( 'update_mode', 'create_new' );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
	}

	public function test_import_variables_with_update_mode(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', ':root { --accent: #00ff00; }' );
		$request->set_param( 'update_mode', 'update' );

		$response = rest_do_request( $request );

		$this->assertTrue( in_array( $response->get_status(), [ 200, 403 ], true ) );
	}

}
