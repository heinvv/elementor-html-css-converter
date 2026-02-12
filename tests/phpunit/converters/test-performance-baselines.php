<?php

namespace ElementorHtmlCssConverter\Tests\Converters;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Conversion_Service;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Extractor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Performance_Baselines extends TestCase {

	private const CSS_EXTRACT_10KB_ITERATIONS = 10;
	private const CSS_EXTRACT_10KB_BUDGET_MS = 500;
	private const VARIABLE_CONVERSION_100_ITERATIONS = 20;
	private const VARIABLE_CONVERSION_100_BUDGET_MS = 500;
	private const VARIABLE_CONVERSION_1000_ITERATIONS = 5;
	private const VARIABLE_CONVERSION_1000_BUDGET_MS = 2000;

	private function generate_css_for_size( int $target_bytes ): string {
		$chunk = "--a: 1px; --b: 2px; --c: #fff; --d: 10rem; --e: sans-serif; ";
		$repeat = (int) ceil( $target_bytes / strlen( $chunk ) );
		return ':root { ' . str_repeat( $chunk, $repeat ) . ' }';
	}

	private function generate_variables_array( int $count ): array {
		$variables = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$variables[] = [
				'name'  => "--var-{$i}",
				'value' => $i % 3 === 0 ? '#ff0000' : ( $i % 3 === 1 ? '16px' : 'sans-serif' ),
			];
		}
		return $variables;
	}

	public function test_variable_extractor_10kb_css_completes_within_budget(): void {
		$extractor = new Variable_Extractor();
		$css = $this->generate_css_for_size( 10 * 1024 );

		$start = microtime( true );
		for ( $i = 0; $i < self::CSS_EXTRACT_10KB_ITERATIONS; $i++ ) {
			$extractor->extract_from_css( $css );
		}
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan(
			self::CSS_EXTRACT_10KB_BUDGET_MS,
			$elapsed_ms,
			sprintf(
				'Variable extraction of 10KB CSS x%d iterations took %.2fms (budget %dms)',
				self::CSS_EXTRACT_10KB_ITERATIONS,
				$elapsed_ms,
				self::CSS_EXTRACT_10KB_BUDGET_MS
			)
		);
	}

	public function test_variable_conversion_100_variables_completes_within_budget(): void {
		$variables = $this->generate_variables_array( 100 );

		$start = microtime( true );
		for ( $i = 0; $i < self::VARIABLE_CONVERSION_100_ITERATIONS; $i++ ) {
			Variable_Conversion_Service::convert_to_editor_variables( $variables );
		}
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan(
			self::VARIABLE_CONVERSION_100_BUDGET_MS,
			$elapsed_ms,
			sprintf(
				'Conversion of 100 variables x%d iterations took %.2fms (budget %dms)',
				self::VARIABLE_CONVERSION_100_ITERATIONS,
				$elapsed_ms,
				self::VARIABLE_CONVERSION_100_BUDGET_MS
			)
		);
	}

	public function test_variable_conversion_1000_variables_completes_within_budget(): void {
		$variables = $this->generate_variables_array( 1000 );

		$start = microtime( true );
		for ( $i = 0; $i < self::VARIABLE_CONVERSION_1000_ITERATIONS; $i++ ) {
			Variable_Conversion_Service::convert_to_editor_variables( $variables );
		}
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan(
			self::VARIABLE_CONVERSION_1000_BUDGET_MS,
			$elapsed_ms,
			sprintf(
				'Conversion of 1000 variables x%d iterations took %.2fms (budget %dms)',
				self::VARIABLE_CONVERSION_1000_ITERATIONS,
				$elapsed_ms,
				self::VARIABLE_CONVERSION_1000_BUDGET_MS
			)
		);
	}

}
