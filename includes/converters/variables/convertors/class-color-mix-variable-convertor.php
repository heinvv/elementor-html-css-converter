<?php

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Mix_Variable_Convertor extends Abstract_Variable_Convertor {

	private const COLOR_MIX_PREFIX = 'color-mix(';

	public function supports( string $name, string $value ): bool {
		$trimmed = strtolower( trim( $value ) );

		if ( 0 !== strpos( $trimmed, self::COLOR_MIX_PREFIX ) ) {
			return false;
		}

		return $this->has_balanced_parentheses( $value );
	}

	protected function get_type(): string {
		return 'color-mix';
	}

	protected function normalize_value( string $value ): string {
		return trim( $value );
	}

	private function has_balanced_parentheses( string $value ): bool {
		$depth = 0;

		for ( $i = 0, $length = strlen( $value ); $i < $length; $i++ ) {
			if ( '(' === $value[ $i ] ) {
				$depth++;
			} elseif ( ')' === $value[ $i ] ) {
				$depth--;
			}

			if ( $depth < 0 ) {
				return false;
			}
		}

		return 0 === $depth;
	}
}
