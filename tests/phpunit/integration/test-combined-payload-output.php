<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;
use PHPUnit\Framework\TestCase;

class Test_Combined_Payload_Output extends TestCase {

	public function test_full_html_css_ids_classes_root_media(): void {
		$fixture = Fixture_Loader::load_json( 'integration/full-import-payload.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_variables', true );
		$request->set_param( 'import_classes', true );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'widgets', $data );
		$this->assertGreaterThanOrEqual( 1, count( $data['widgets'] ) );

		$widget_ids = array_column( $data['widgets'], 'id' );
		$this->assertNotEmpty( array_filter( $widget_ids ) );
	}

	public function test_widget_tree_has_required_keys(): void {
		$fixture = Fixture_Loader::load_json( 'integration/responsive-hero.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'] );
		foreach ( $data['widgets'] as $widget ) {
			$this->assertArrayHasKey( 'id', $widget );
			$this->assertArrayHasKey( 'elType', $widget );
			$this->assertArrayHasKey( 'widgetType', $widget );
			$this->assertArrayHasKey( 'settings', $widget );
		}
	}

}
