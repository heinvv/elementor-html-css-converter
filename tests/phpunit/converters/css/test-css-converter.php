<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Css_Converter extends TestCase {

	public function test_convert_properties__empty_registry_puts_all_in_custom_css(): void {
		$registry = new Converter_Registry();
		$converter = new Css_Converter( $registry );

		$properties = [
			'color'   => '#ff0000',
			'padding'  => '16px',
			'unknown'  => 'value',
		];
		$result = $converter->convert_properties( $properties );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'customCss', $result );
		$this->assertEmpty( $result['props'] );
		$this->assertNotEmpty( $result['customCss'] );
	}

	public function test_convert_properties__substitutes_var_with_fallback(): void {
		$registry = new Converter_Registry();
		$converter = new Css_Converter( $registry );

		$properties = [
			'gap' => 'var(--spacing)',
		];
		$fallback = [ '--spacing' => '16px' ];
		$result = $converter->convert_properties( $properties, $fallback );

		$this->assertStringContainsString( '16px', $result['customCss'] ?? '' );
	}

	public function test_convert__parses_css_string(): void {
		$registry  = new Converter_Registry();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [
			'cssString' => 'color: red; padding: 20px;',
		] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'customCss', $result );
	}

	public function test_convert__empty_css_returns_valid_structure(): void {
		$registry  = new Converter_Registry();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => '' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertIsArray( $result['props'] );
		$this->assertEmpty( $result['props'] );
	}

	public function test_convert_properties__empty_array_returns_valid_structure(): void {
		$registry  = new Converter_Registry();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert_properties( [] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertEmpty( $result['props'] );
	}

}


