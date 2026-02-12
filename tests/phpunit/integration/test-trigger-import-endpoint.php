<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use PHPUnit\Framework\TestCase;

class Test_Trigger_Import_Endpoint extends TestCase {

	public function test_post_trigger_import_accepts_payload(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/trigger-import' );
		$request->set_param( 'url', 'https://example.com/' );
		$request->set_param( 'selectors', '#header, .card' );
		$request->set_param( 'timeout', '60' );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'message', $data );
	}

	public function test_trigger_import_returns_job_id_on_success(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/trigger-import' );
		$request->set_param( 'url', 'https://example.com/' );
		$request->set_param( 'selectors', '#main' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'job_id', $data );
		if ( ! empty( $data['job_id'] ) ) {
			$this->assertStringStartsWith( 'wp-', $data['job_id'] );
		}
	}

}
