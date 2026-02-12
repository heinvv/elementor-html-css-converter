<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use PHPUnit\Framework\TestCase;

class Test_Convert_Html_Endpoint extends TestCase {

	public function test_post_convert_html_returns_success(): void {
		$html = '<style>#hero { padding: 80px; }</style><div id="hero">Hero</div>';
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $html );
		$request->set_param( 'import_variables', false );
		$request->set_param( 'import_classes', false );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'widgets', $data );
	}

	public function test_convert_html_with_classes_and_variables(): void {
		$html = '<style>:root { --primary: #f00; } .card { padding: 20px; }</style><div class="card">Content</div>';
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $html );
		$request->set_param( 'import_variables', true );
		$request->set_param( 'import_classes', true );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}

	public function test_convert_html_with_breakpoints(): void {
		$html = '<style>#hero { padding: 80px; }
@media (max-width: 1024px) { #hero { padding: 60px; } }
@media (max-width: 767px) { #hero { padding: 40px; } }
</style><div id="hero">Hero</div>';
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $html );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}

	public function test_convert_html_empty_returns_error(): void {
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', '' );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'error', $data );
	}

}
