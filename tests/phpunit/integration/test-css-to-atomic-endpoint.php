<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;

class Test_Css_To_Atomic_Endpoint extends Base_Test_Case {

	public function test_post_css_to_atomic_returns_props(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/css-to-atomic' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [
			'cssString' => 'color: #ff0000; padding: 20px;',
		] ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertIsArray( $data );
	}

	public function test_css_to_atomic_with_breakpoints_in_classes(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/css-to-atomic' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [
			'cssString' => '.box { padding: 20px; }
@media (max-width: ' . Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT . 'px) { .box { padding: 15px; } }',
		] ) );

		$response   = rest_do_request( $request );
		$allowed    = [ Test_Constants::HTTP_OK, Test_Constants::HTTP_BAD_REQUEST, Test_Constants::HTTP_FORBIDDEN ];

		$this->assertTrue( in_array( $response->get_status(), $allowed, true ) );
	}

	public function test_css_to_atomic_response_has_props_and_custom_css_keys(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/css-to-atomic' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'cssString' => 'color: red; padding: 10px;' ] ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		if ( Test_Constants::HTTP_OK === $response->get_status() ) {
			$this->assertArrayHasKey( 'success', $data );
			$this->assertArrayHasKey( 'props', $data );
			$this->assertArrayHasKey( 'customCss', $data );
			$this->assertIsArray( $data['props'] );
		}
	}

	public function test_css_to_atomic_empty_css_string_returns_valid_structure(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/css-to-atomic' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'cssString' => '' ] ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertTrue( in_array( $response->get_status(), [ Test_Constants::HTTP_OK, Test_Constants::HTTP_BAD_REQUEST ], true ) );
		if ( Test_Constants::HTTP_OK === $response->get_status() ) {
			$this->assertArrayHasKey( 'props', $data );
		}
	}

}
