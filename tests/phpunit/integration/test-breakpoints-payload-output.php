<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;

class Test_Breakpoints_Payload_Output extends Base_Test_Case {

	public function test_media_queries_produce_breakpoint_props(): void {
		$fixture = Fixture_Loader::load_json( 'integration/responsive-hero.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSuccessfulConversion( $data );

		$has_breakpoint_styles = false;
		foreach ( $data['widgets'] as $widget ) {
			if ( ! empty( $widget['styles'] ) ) {
				foreach ( $widget['styles'] as $style_def ) {
					$variants    = $style_def['variants'] ?? [];
					$breakpoints = array_filter( array_column( $variants, 'breakpoint' ) );
					if ( count( $breakpoints ) > 1 ) {
						$has_breakpoint_styles = true;
						break 2;
					}
				}
			}
		}
		$this->assertTrue( $has_breakpoint_styles );
	}

	public function test_full_import_payload_converts_successfully(): void {
		$fixture = Fixture_Loader::load_json( 'integration/full-import-payload.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_variables', $fixture['import_variables'] ?? true );
		$request->set_param( 'import_classes', $fixture['import_classes'] ?? true );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSuccessfulConversion( $data );
	}

}
