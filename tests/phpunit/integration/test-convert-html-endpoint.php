<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;

class Test_Convert_Html_Endpoint extends Base_Test_Case {

	public function test_post_convert_html_returns_success(): void {
		$fixture = Fixture_Loader::load_json( 'integration/hero-id-simple.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_variables', false );
		$request->set_param( 'import_classes', false );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertSuccessfulConversion( $data );
		$this->assertValidWidgetStructure( $data['widgets'][0] );
		$this->assertWidgetHasStylesOrSettings( $data['widgets'][0] );
	}

	public function test_convert_html_with_classes_and_variables(): void {
		$html = '<style>:root { --primary: #f00; } .card { padding: 20px; }</style><div class="card">Content</div>';
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $html );
		$request->set_param( 'import_variables', true );
		$request->set_param( 'import_classes', true );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertSuccessfulConversion( $data );
	}

	public function test_convert_html_with_breakpoints(): void {
		$fixture = Fixture_Loader::load_json( 'integration/hero-responsive-breakpoints.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertSuccessfulConversion( $data );
		$this->assertValidWidgetStructure( $data['widgets'][0] );
		$this->assertWidgetHasStylesOrSettings( $data['widgets'][0] );
		$this->assertFirstWidgetHasBreakpointVariants( $data['widgets'] );
	}

	public function test_convert_html_empty_returns_error(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', '' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
		$this->assertNotEmpty( $data['error'] );
		$this->assertEmpty( $data['widgets'] ?? [] );
	}

	public function test_convert_html_missing_html_returns_validation_error(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );

		$response = rest_do_request( $request );

		$this->assertSame( Test_Constants::HTTP_BAD_REQUEST, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'message', $data );
	}

	public function test_convert_html_response_has_expected_keys_on_success(): void {
		$fixture = Fixture_Loader::load_json( 'integration/hero-id-simple.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'widgets', $data );
		$this->assertIsArray( $data['widgets'] );
		if ( ! empty( $data['widgets'] ) ) {
			$this->assertArrayHasKey( 'id', $data['widgets'][0] );
			$this->assertArrayHasKey( 'elType', $data['widgets'][0] );
			$this->assertArrayHasKey( 'widgetType', $data['widgets'][0] );
		}
	}

}
