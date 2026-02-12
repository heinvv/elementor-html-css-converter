<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Conversion_Service;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Variable_Conversion_Service extends TestCase {

	public function test_convert_to_editor_variables__converts_supported(): void {
		$variables = [
			[ 'name' => '--primary', 'value' => '#ff0000' ],
			[ 'name' => '--spacing', 'value' => '16px' ],
		];
		$result = Variable_Conversion_Service::convert_to_editor_variables( $variables );

		$this->assertCount( 2, $result['converted'] );
		$this->assertSame( 'color-hex', $result['converted'][0]['type'] );
		$this->assertSame( 'size-length-viewport', $result['converted'][1]['type'] );
		$this->assertCount( 0, $result['skipped'] );
	}

	public function test_convert_to_editor_variables__skips_unsupported(): void {
		$variables = [
			[ 'name' => '--unknown', 'value' => 'some-unsupported-value' ],
		];
		$result = Variable_Conversion_Service::convert_to_editor_variables( $variables );

		$this->assertCount( 0, $result['converted'] );
		$this->assertCount( 1, $result['skipped'] );
		$this->assertSame( '--unknown', $result['skipped'][0]['name'] );
		$this->assertSame( 'some-unsupported-value', $result['skipped'][0]['value'] );
	}

	public function test_convert_to_editor_variables__mixed_supported_and_skipped(): void {
		$variables = [
			[ 'name' => '--color', 'value' => 'red' ],
			[ 'name' => '--unknown', 'value' => '???' ],
			[ 'name' => '--size', 'value' => '50%' ],
		];
		$result = Variable_Conversion_Service::convert_to_editor_variables( $variables );

		$this->assertCount( 2, $result['converted'] );
		$this->assertCount( 1, $result['skipped'] );
		$this->assertSame( '--unknown', $result['skipped'][0]['name'] );
	}

	public function test_convert_to_editor_variables__ignores_invalid_entries(): void {
		$variables = [
			[ 'name' => '--primary', 'value' => 123 ],
			[ 'name' => null, 'value' => '#fff' ],
			[ 'name' => '--color', 'value' => '#fff' ],
		];
		$result = Variable_Conversion_Service::convert_to_editor_variables( $variables );

		$this->assertCount( 1, $result['converted'] );
	}

	public function test_convert_to_editor_variables__empty_input(): void {
		$result = Variable_Conversion_Service::convert_to_editor_variables( [] );

		$this->assertCount( 0, $result['converted'] );
		$this->assertCount( 0, $result['skipped'] );
	}

}
