<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Properties\Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Converter_Registry extends TestCase {

	private Converter_Registry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new Converter_Registry();
	}

	public function test_resolve__returns_null_when_empty(): void {
		$this->assertNull( $this->registry->resolve( 'color' ) );
		$this->assertNull( $this->registry->resolve( 'padding' ) );
	}

	public function test_register_and_resolve__returns_converter(): void {
		$color_converter = new Color_Converter();
		$this->registry->register( $color_converter );

		$resolved = $this->registry->resolve( 'color' );
		$this->assertInstanceOf( Property_Converter_Interface::class, $resolved );
		$this->assertInstanceOf( Color_Converter::class, $resolved );
	}

	public function test_resolve__returns_null_for_unknown_property(): void {
		$this->registry->register( new Color_Converter() );

		$this->assertNull( $this->registry->resolve( 'unknown-property' ) );
	}

}
