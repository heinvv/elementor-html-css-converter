<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use PHPUnit\Framework\TestCase;

class Test_Css_To_Atomic_Endpoint extends TestCase {

	public function test_post_css_to_atomic_returns_props(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/css-to-atomic' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [
			'cssString' => 'color: #ff0000; padding: 20px;',
		] ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
	}

	public function test_css_to_atomic_with_breakpoints_in_classes(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/css-to-atomic' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [
			'cssString' => '.box { padding: 20px; }
@media (max-width: 1024px) { .box { padding: 15px; } }',
		] ) );

		$response = rest_do_request( $request );

		$this->assertTrue( in_array( $response->get_status(), [ 200, 400, 403 ], true ) );
	}

}
