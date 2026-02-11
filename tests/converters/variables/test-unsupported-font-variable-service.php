<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Unsupported_Font_Variable_Service;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Unsupported_Font_Variable_Service extends TestCase {

	public function test_generate_font_hash_is_deterministic(): void {
		$service = new Unsupported_Font_Variable_Service();
		$repository = $this->create_mock_repository();

		$result1 = $service->get_or_create_variable( 'Unknown Font', $repository );
		$result2 = $service->get_or_create_variable( 'Unknown Font', $repository );

		$this->assertNotNull( $result1 );
		$this->assertNotNull( $result2 );
		$this->assertStringStartsWith( 'unsupported-font-family-', $result1['label'] );
		$this->assertSame( $result1['label'], $result2['label'] );
		$this->assertSame( 8, strlen( substr( $result1['label'], strlen( 'unsupported-font-family-' ) ) ) );
	}

	public function test_different_fonts_get_different_hashes(): void {
		$service = new Unsupported_Font_Variable_Service();
		$repository = $this->create_mock_repository();

		$result1 = $service->get_or_create_variable( 'Unknown Font A', $repository );
		$result2 = $service->get_or_create_variable( 'Unknown Font B', $repository );

		$this->assertNotSame( $result1['label'], $result2['label'] );
	}

	public function test_returns_null_for_empty_font(): void {
		$service = new Unsupported_Font_Variable_Service();
		$repository = $this->create_mock_repository();

		$result = $service->get_or_create_variable( '   ', $repository );

		$this->assertNull( $result );
	}

	private function create_mock_repository() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			$this->markTestSkipped( 'Requires Elementor.' );
		}

		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( null === $kit ) {
			$this->markTestSkipped( 'Requires active Elementor kit.' );
		}

		return new \Elementor\Modules\Variables\Storage\Repository( $kit );
	}
}
