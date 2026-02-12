<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Html;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Html\Html_Converter;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Html_Converter extends TestCase {

	private function create_converter(): Html_Converter {
		$registry = new Converter_Registry();
		return new Html_Converter( $registry );
	}

	public function test_get_atomic_widgets_status__returns_unavailable_without_elementor(): void {
		$converter = $this->create_converter();
		$status = $converter->get_atomic_widgets_status();

		$this->assertFalse( $status['available'] );
		$this->assertArrayHasKey( 'reason', $status );
	}

	public function test_convert_html_to_atomic_widgets__returns_error_without_elementor(): void {
		$converter = $this->create_converter();
		$result = $converter->convert_html_to_atomic_widgets( '<div>test</div>' );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'error', $result );
	}

	public function test_convert_html_to_atomic_widgets__empty_html_returns_error(): void {
		$converter = $this->create_converter();
		$result = $converter->convert_html_to_atomic_widgets( '' );

		$this->assertFalse( $result['success'] );
	}

	public function test_get_supported_html_tags__returns_array(): void {
		$converter = $this->create_converter();
		$tags = $converter->get_supported_html_tags();

		$this->assertIsArray( $tags );
		$this->assertContains( 'div', $tags );
		$this->assertContains( 'p', $tags );
		$this->assertContains( 'a', $tags );
	}

}
