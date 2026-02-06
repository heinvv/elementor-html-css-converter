<?php

namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Property_Converter_Base implements Property_Converter_Interface {
	abstract protected function get_supported_properties_list(): array;

	public function supports( string $property, $value = null ): bool {
		return in_array( $property, $this->get_supported_properties(), true );
	}

	public function get_supported_properties(): array {
		return $this->get_supported_properties_list();
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		if ( Variable_Resolver::is_css_variable( $value ) ) {
			$variable_type = $this->get_variable_type();

			if ( null === $variable_type ) {
				return null;
			}

			$resolved = Variable_Resolver::resolve( $value, $variable_type );

			if ( null === $resolved ) {
				return null;
			}

			return $this->wrap_resolved_variable( $resolved, $property );
		}

		return $this->convert_value( $property, $value );
	}

	abstract protected function convert_value( string $property, $value ): ?array;

	protected function get_variable_type(): ?string {
		return null;
	}

	protected function wrap_resolved_variable( array $resolved, string $property ): array {
		return $resolved;
	}

	public function get_output_property( string $property ): string {
		return $property;
	}
}
