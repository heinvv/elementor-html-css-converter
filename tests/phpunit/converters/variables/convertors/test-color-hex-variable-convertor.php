<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Hex_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Hex_Variable_Convertor extends TestCase {

	private Color_Hex_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Hex_Variable_Convertor();
	}

	public function hex_supports_valid_provider(): array {
		return [
			'hex3_fff' => [ '#fff' ],
			'hex3_f00' => [ '#f00' ],
			'hex3_abc' => [ '#abc' ],
			'hex6_ffffff' => [ '#ffffff' ],
			'hex6_ff0000' => [ '#ff0000' ],
			'hex6_uppercase' => [ '#ABCDEF' ],
			'hex8_with_alpha' => [ '#ffffff80' ],
		];
	}

	/**
	 * @dataProvider hex_supports_valid_provider
	 */
	public function test_supports__accepts_valid_hex( string $value ): void {
		$this->assertTrue( $this->convertor->supports( '--color', $value ) );
	}

	public function hex_supports_invalid_provider(): array {
		return [
			'invalid_chars' => [ '#gg' ],
			'wrong_length' => [ '#12345' ],
			'rgb_not_hex' => [ 'rgb(0,0,0)' ],
			'empty_string' => [ '' ],
		];
	}

	/**
	 * @dataProvider hex_supports_invalid_provider
	 */
	public function test_supports__rejects_invalid_hex( string $value ): void {
		$this->assertFalse( $this->convertor->supports( '--color', $value ) );
	}

	public function hex_convert_normalizes_provider(): array {
		return [
			'hex3_to_hex6' => [ '#fff', '#ffffff' ],
			'hex6_unchanged' => [ '#ff5733', '#ff5733' ],
			'hex8_preserved' => [ '#ffffff80', '#ffffff80' ],
			'uppercase_lowercased' => [ '#FFF', '#ffffff' ],
			'hex3_f00' => [ '#f00', '#ff0000' ],
		];
	}

	/**
	 * @dataProvider hex_convert_normalizes_provider
	 */
	public function test_convert__normalizes_hex_value( string $input, string $expected_value ): void {
		$result = $this->convertor->convert( '--color', $input );
		$this->assertSame( $expected_value, $result['value'] );
	}

	public function test_get_type__returns_color_hex(): void {
		$result = $this->convertor->convert( '--primary', '#f00' );
		$this->assertSame( 'color-hex', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--primary-color', '#fff' );
		$this->assertSame( 'e-gv-color-hex-primary-color-variable', $result['id'] );
	}

	public function test_convert__sets_source_and_name(): void {
		$result = $this->convertor->convert( '--accent', '#000' );
		$this->assertSame( 'css-variable', $result['source'] );
		$this->assertSame( '--accent', $result['name'] );
	}

}
