<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;

class Test_Breakpoints_Endpoint extends Base_Test_Case {

	public function test_get_breakpoints_returns_200(): void {
		$request  = new \WP_REST_Request( 'GET', '/html-css-converter/v1/breakpoints' );
		$response = rest_do_request( $request );

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
	}

	public function test_get_breakpoints_returns_breakpoints_array(): void {
		$request  = new \WP_REST_Request( 'GET', '/html-css-converter/v1/breakpoints' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'breakpoints', $data );
		$this->assertIsArray( $data['breakpoints'] );
	}

	public function test_breakpoints_have_name_width_direction(): void {
		$request  = new \WP_REST_Request( 'GET', '/html-css-converter/v1/breakpoints' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		if ( empty( $data['breakpoints'] ) ) {
			$this->markTestSkipped( 'No breakpoints configured.' );
		}

		$first = $data['breakpoints'][0];
		$this->assertArrayHasKey( 'name', $first );
		$this->assertArrayHasKey( 'width', $first );
		$this->assertArrayHasKey( 'direction', $first );
	}

	public function test_breakpoints_response_schema(): void {
		$request  = new \WP_REST_Request( 'GET', '/html-css-converter/v1/breakpoints' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'breakpoints', $data );
		$this->assertIsArray( $data['breakpoints'] );
		foreach ( $data['breakpoints'] as $bp ) {
			$this->assertArrayHasKey( 'name', $bp );
			$this->assertArrayHasKey( 'width', $bp );
			$this->assertArrayHasKey( 'direction', $bp );
		}
	}

}
