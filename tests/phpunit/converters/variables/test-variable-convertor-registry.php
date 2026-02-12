<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Convertor_Registry;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Hex_Variable_Convertor;
use ElementorHtmlCssConverter\Converters\Variables\Convertors\Percentage_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Variable_Convertor_Registry extends TestCase {

	private Variable_Convertor_Registry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new Variable_Convertor_Registry();
	}

	public function test_resolve__returns_convertor_for_hex(): void {
		$convertor = $this->registry->resolve( '--color', '#ff0000' );
		$this->assertInstanceOf( Color_Hex_Variable_Convertor::class, $convertor );
	}

	public function test_resolve__returns_convertor_for_percentage(): void {
		$convertor = $this->registry->resolve( '--width', '50%' );
		$this->assertInstanceOf( Percentage_Variable_Convertor::class, $convertor );
	}

	public function test_resolve__returns_convertor_for_rgb(): void {
		$convertor = $this->registry->resolve( '--color', 'rgb(255, 0, 0)' );
		$this->assertNotNull( $convertor );
		$this->assertTrue( $convertor->supports( '--color', 'rgb(255, 0, 0)' ) );
	}

	public function test_resolve__returns_null_for_unsupported(): void {
		$this->assertNull( $this->registry->resolve( '--x', 'unsupported-value' ) );
	}

	public function test_resolve__can_convert_through_resolved_convertor(): void {
		$convertor = $this->registry->resolve( '--primary', '#fff' );
		$this->assertNotNull( $convertor );
		$result = $convertor->convert( '--primary', '#fff' );
		$this->assertSame( 'color-hex', $result['type'] );
		$this->assertSame( '#ffffff', $result['value'] );
	}

}
