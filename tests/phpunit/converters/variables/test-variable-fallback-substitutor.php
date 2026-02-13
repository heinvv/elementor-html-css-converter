<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Fallback_Substitutor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Variable_Fallback_Substitutor extends TestCase {

	public function test_build_fallback_map_from_css__extracts_variables(): void {
		$css = ':root { --primary: #ff0000; --spacing: 16px; }';
		$map = Variable_Fallback_Substitutor::build_fallback_map_from_css( $css );

		$this->assertSame( '#ff0000', $map['--primary'] );
		$this->assertSame( '16px', $map['--spacing'] );
	}

	public function test_build_fallback_map_from_css__empty_css(): void {
		$map = Variable_Fallback_Substitutor::build_fallback_map_from_css( '' );
		$this->assertSame( [], $map );
	}

	public function test_substitute_in_value__replaces_known_var(): void {
		$fallback_map = [ '--gap' => '16px' ];
		$value = 'var(--gap)';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, $fallback_map );

		$this->assertSame( '16px', $result );
	}

	public function test_substitute_in_value__preserves_unknown_var(): void {
		$fallback_map = [ '--gap' => '16px' ];
		$value = 'var(--unknown)';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, $fallback_map );

		$this->assertSame( 'var(--unknown)', $result );
	}

	public function test_substitute_in_value__replaces_with_fallback_in_value(): void {
		$fallback_map = [ '--gap' => '1rem' ];
		$value = 'calc(100% - var(--gap))';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, $fallback_map );

		$this->assertSame( 'calc(100% - 1rem)', $result );
	}

	public function test_substitute_in_value__multiple_vars(): void {
		$fallback_map = [ '--a' => '10px', '--b' => '20px' ];
		$value = 'var(--a) var(--b)';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, $fallback_map );

		$this->assertSame( '10px 20px', $result );
	}

	public function test_substitute_in_value__empty_map_returns_unchanged(): void {
		$value = 'var(--gap)';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, [] );
		$this->assertSame( 'var(--gap)', $result );
	}

	public function test_substitute_in_value__no_var_returns_unchanged(): void {
		$fallback_map = [ '--gap' => '16px' ];
		$value = '16px';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, $fallback_map );
		$this->assertSame( '16px', $result );
	}

	public function test_substitute_in_value__tailwind_rgb_vars(): void {
		$fallback_map = [ '--color-tertiary-500' => '38 173 231', '--tw-bg-opacity' => '1' ];
		$value = 'rgb(var(--color-tertiary-500)/var(--tw-bg-opacity,1))';
		$result = Variable_Fallback_Substitutor::substitute_in_value( $value, $fallback_map );

		$this->assertSame( 'rgb(38 173 231/1)', $result );
	}

}
