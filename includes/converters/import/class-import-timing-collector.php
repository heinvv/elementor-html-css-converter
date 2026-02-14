<?php
/**
 * Import Timing Collector
 *
 * Records step durations for import process profiling. Used only when EHCC_DEBUG_TIMING or WP_DEBUG is enabled.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Timing_Collector {

	private array $steps = [];

	public function start( string $step ): float {
		return microtime( true );
	}

	public function record( string $step, float $start ): void {
		$this->steps[ $step ] = (int) round( ( microtime( true ) - $start ) * 1000 );
	}

	public function record_ms( string $step, int $ms ): void {
		$this->steps[ $step ] = $ms;
	}

	public function get_all(): array {
		return $this->steps;
	}

	public function wrap( string $step, callable $fn ) {
		$t0 = microtime( true );
		$result = $fn();
		$this->record( $step, $t0 );
		return $result;
	}
}

