<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;
use PHPUnit\Framework\TestCase;

class Test_Variables_Payload_Output extends TestCase {

	public function test_root_simple_produces_variables_array(): void {
		$fixture = Fixture_Loader::load_json( 'variables/root-simple.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/import-variables' );
		$request->set_param( 'css', $fixture['css'] );
		$request->set_param( 'update_mode', 'create_new' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
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
