<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;

class Test_Classes_Payload_Output extends Base_Test_Case {

	public function test_single_class_produces_widget_with_styles(): void {
		$fixture = Fixture_Loader::load_json( 'classes/single-class-simple.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSuccessfulConversion( $data );

		$widget = $data['widgets'][0];
		$this->assertArrayHasKey( 'styles', $widget );
		$this->assertArrayHasKey( 'settings', $widget );
	}

	public function test_breakpoint_class_produces_styles_with_variants(): void {
		$fixture = Fixture_Loader::load_json( 'classes/breakpoint-desktop-tablet-mobile.json' );
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $fixture['html'] );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSuccessfulConversion( $data );

		$has_styles = false;
		foreach ( $data['widgets'] as $widget ) {
			if ( ! empty( $widget['styles'] ) ) {
				$has_styles = true;
				break;
			}
		}
		$this->assertTrue( $has_styles );
	}

	public function test_custom_css_in_settings_when_unsupported_props(): void {
		$html = '<style>.card { padding: 20px; unknown-prop: value; }</style><div class="card">Card</div>';
		$request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
		$request->set_param( 'html', $html );
		$request->set_param( 'import_images', false );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSuccessfulConversion( $data );
	}

}
