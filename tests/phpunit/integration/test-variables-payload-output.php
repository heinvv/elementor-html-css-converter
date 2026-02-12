<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;

class Test_Variables_Payload_Output extends Base_Test_Case {

	public function test_root_simple_produces_variables_array(): void {
		$fixture = Fixture_Loader::load_json( 'variables/root-simple.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', $fixture['css'] );
		$request->set_param( 'update_mode', 'create_new' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( Test_Constants::HTTP_OK, $response->get_status() );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'variables', $data );
		$this->assertIsArray( $data['variables'] );
	}

	public function test_variables_response_has_storage_counts(): void {
		$fixture = Fixture_Loader::load_json( 'variables/root-simple.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', $fixture['css'] );
		$request->set_param( 'update_mode', 'create_new' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'created', $data );
		$this->assertArrayHasKey( 'reused', $data );
		$this->assertArrayHasKey( 'updated', $data );
	}

}
