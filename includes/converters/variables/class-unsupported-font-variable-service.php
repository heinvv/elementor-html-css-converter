<?php

namespace ElementorHtmlCssConverter\Converters\Variables;

use Elementor\Modules\Variables\Storage\Repository as Variables_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Unsupported_Font_Variable_Service {

	private const LABEL_PREFIX = 'unsupported-font-family-';
	private const HASH_LENGTH = 8;
	private const ELEMENTOR_FONT_VARIABLE_TYPE = 'global-font-variable';

	public function get_or_create_variable( string $font_name, Variables_Repository $repository ): ?array {
		$normalized_font = $this->normalize_font_name( $font_name );
		if ( '' === $normalized_font ) {
			return null;
		}

		$hash = $this->generate_font_hash( $normalized_font );
		$label = self::LABEL_PREFIX . $hash;

		$existing = $this->find_variable_by_label( $repository, $label );
		if ( null !== $existing ) {
			return [
				'id'    => $existing['id'],
				'font'  => $normalized_font,
				'label' => $label,
			];
		}

		try {
			$result = $repository->create( [
				'type'  => self::ELEMENTOR_FONT_VARIABLE_TYPE,
				'label' => $label,
				'value' => $normalized_font,
			] );
		} catch ( \Exception $e ) {
			return null;
		}

		$variable = $result['variable'] ?? null;
		if ( null === $variable || ! isset( $variable['id'] ) ) {
			return null;
		}

		return [
			'id'    => $variable['id'],
			'font'  => $normalized_font,
			'label' => $label,
		];
	}

	private function normalize_font_name( string $font ): string {
		$font = trim( $font );

		if ( ( str_starts_with( $font, '"' ) && str_ends_with( $font, '"' ) ) ||
			 ( str_starts_with( $font, "'" ) && str_ends_with( $font, "'" ) ) ) {
			$font = substr( $font, 1, -1 );
		}

		$font = trim( $font );
		$font = preg_replace( '/\s+/', ' ', $font );

		return $font;
	}

	private function generate_font_hash( string $normalized_font ): string {
		$hex_hash = md5( strtolower( $normalized_font ) );
		return substr( $hex_hash, 0, self::HASH_LENGTH );
	}

	private function find_variable_by_label( Variables_Repository $repository, string $label ): ?array {
		$db_record = $repository->load();
		$existing  = isset( $db_record['data'] ) && is_array( $db_record['data'] ) ? $db_record['data'] : [];
		$label_lower = strtolower( $label );

		foreach ( $existing as $id => $item ) {
			if ( isset( $item['deleted'] ) && $item['deleted'] ) {
				continue;
			}

			if ( isset( $item['label'] ) && strtolower( $item['label'] ) === $label_lower ) {
				return [
					'id'    => $id,
					'label' => $item['label'],
				];
			}
		}

		return null;
	}
}
