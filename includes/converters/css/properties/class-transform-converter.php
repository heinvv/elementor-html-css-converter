<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Functions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Move_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Scale_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Rotate_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Skew_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Transform_Converter extends Property_Converter_Base {

	private const REGEX_TRANSFORM_FUNCTION_EXTRACTION = '/(\w+)\s*\(\s*([^)]+)\s*\)/i';
	private const REGEX_FUNCTION_ARGS_SPLIT = '/[,\s]+/';
	private const REGEX_ANGLE_VALUE_PARSING = '/^(-?\d*\.?\d+)(deg|rad|grad|turn)?$/i';

	private const SUPPORTED_PROPERTIES = [
		'transform',
		'transform-origin',
		'perspective',
		'perspective-origin',
	];

	private const TRANSFORM_FUNCTIONS = [
		'translate'   => 'move',
		'translatex'  => 'move',
		'translatey'  => 'move',
		'translatez'  => 'move',
		'translate3d' => 'move',
		'scale'       => 'scale',
		'scalex'      => 'scale',
		'scaley'      => 'scale',
		'scalez'      => 'scale',
		'scale3d'     => 'scale',
		'rotate'      => 'rotate',
		'rotatex'     => 'rotate',
		'rotatey'     => 'rotate',
		'rotatez'     => 'rotate',
		'rotate3d'    => 'rotate',
		'skew'        => 'skew',
		'skewx'       => 'skew',
		'skewy'       => 'skew',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return 'transform';
	}

	protected function convert_value( string $property, $value ): ?array {
		return null;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		switch ( $property ) {
			case 'transform':
				return $this->parse_transform_value( $value );
			case 'transform-origin':
				return $this->parse_transform_origin( $value );
			case 'perspective':
				return $this->parse_perspective( $value );
			case 'perspective-origin':
				return $this->parse_perspective_origin( $value );
			default:
				return null;
		}
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_transform_value( string $value ): ?array {
		$value = trim( $value );

		if ( empty( $value ) || 'none' === strtolower( $value ) ) {
			return Transform_Prop_Type::generate( [
				'transform-functions' => [],
			] );
		}

		$functions = $this->parse_transform_functions( $value );

		if ( empty( $functions ) ) {
			return null;
		}

		return Transform_Prop_Type::generate( [
			'transform-functions' => Transform_Functions_Prop_Type::generate( $functions ),
		] );
	}

	private function parse_transform_functions( string $value ): array {
		$functions = [];

		if ( preg_match_all( self::REGEX_TRANSFORM_FUNCTION_EXTRACTION, $value, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$function_name = strtolower( $match[1] );
				$function_args = trim( $match[2] );

				if ( ! isset( self::TRANSFORM_FUNCTIONS[ $function_name ] ) ) {
					continue;
				}

				$function_type = self::TRANSFORM_FUNCTIONS[ $function_name ];
				$parsed_function = $this->parse_transform_function( $function_name, $function_args, $function_type );

				if ( null !== $parsed_function ) {
					$functions[] = $parsed_function;
				}
			}
		}

		return $functions;
	}

	private function parse_transform_function( string $function_name, string $args, string $function_type ): ?array {
		switch ( $function_type ) {
			case 'move':
				return $this->parse_move_function( $function_name, $args );
			case 'scale':
				return $this->parse_scale_function( $function_name, $args );
			case 'rotate':
				return $this->parse_rotate_function( $function_name, $args );
			case 'skew':
				return $this->parse_skew_function( $function_name, $args );
			default:
				return null;
		}
	}

	private function parse_move_function( string $function_name, string $args ): ?array {
		$values = $this->split_function_args( $args );

		$move_data = [];

		switch ( $function_name ) {
			case 'translate':
				$move_data['x'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[0] ?? '0' ) );
				$move_data['y'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[1] ?? $values[0] ?? '0' ) );
				break;
			case 'translatex':
				$move_data['x'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[0] ?? '0' ) );
				$move_data['y'] = Size_Prop_Type::generate( $this->create_zero_size() );
				break;
			case 'translatey':
				$move_data['x'] = Size_Prop_Type::generate( $this->create_zero_size() );
				$move_data['y'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[0] ?? '0' ) );
				break;
			case 'translatez':
				$move_data['x'] = Size_Prop_Type::generate( $this->create_zero_size() );
				$move_data['y'] = Size_Prop_Type::generate( $this->create_zero_size() );
				$move_data['z'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[0] ?? '0' ) );
				break;
			case 'translate3d':
				$move_data['x'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[0] ?? '0' ) );
				$move_data['y'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[1] ?? '0' ) );
				$move_data['z'] = Size_Prop_Type::generate( $this->parse_size_or_zero( $values[2] ?? '0' ) );
				break;
			default:
				return null;
		}

		return Transform_Move_Prop_Type::generate( $move_data );
	}

	private function parse_scale_function( string $function_name, string $args ): ?array {
		$values = $this->split_function_args( $args );

		$scale_data = [];

		switch ( $function_name ) {
			case 'scale':
				$scale_value = round( (float) ( $values[0] ?? 1 ), 10 );
				$scale_data['x'] = $scale_value;
				$scale_data['y'] = round( (float) ( $values[1] ?? $scale_value ), 10 );
				break;
			case 'scalex':
				$scale_data['x'] = round( (float) ( $values[0] ?? 1 ), 10 );
				$scale_data['y'] = 1.0;
				break;
			case 'scaley':
				$scale_data['x'] = 1.0;
				$scale_data['y'] = round( (float) ( $values[0] ?? 1 ), 10 );
				break;
			case 'scalez':
				$scale_data['x'] = 1.0;
				$scale_data['y'] = 1.0;
				$scale_data['z'] = round( (float) ( $values[0] ?? 1 ), 10 );
				break;
			case 'scale3d':
				$scale_data['x'] = round( (float) ( $values[0] ?? 1 ), 10 );
				$scale_data['y'] = round( (float) ( $values[1] ?? 1 ), 10 );
				$scale_data['z'] = round( (float) ( $values[2] ?? 1 ), 10 );
				break;
			default:
				return null;
		}

		return Transform_Scale_Prop_Type::generate( $scale_data );
	}

	private function parse_rotate_function( string $function_name, string $args ): ?array {
		$values = $this->split_function_args( $args );

		$rotate_data = [];

		switch ( $function_name ) {
			case 'rotate':
				$rotate_data['z'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				break;
			case 'rotatex':
				$rotate_data['x'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				break;
			case 'rotatey':
				$rotate_data['y'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				break;
			case 'rotatez':
				$rotate_data['z'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				break;
			case 'rotate3d':
				$rotate_data['z'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[3] ?? '0deg' ) );
				break;
			default:
				return null;
		}

		return Transform_Rotate_Prop_Type::generate( $rotate_data );
	}

	private function parse_skew_function( string $function_name, string $args ): ?array {
		$values = $this->split_function_args( $args );

		$skew_data = [];

		switch ( $function_name ) {
			case 'skew':
				$skew_data['x'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				$skew_data['y'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[1] ?? '0deg' ) );
				break;
			case 'skewx':
				$skew_data['x'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				$skew_data['y'] = Size_Prop_Type::generate( $this->parse_angle_value( '0deg' ) );
				break;
			case 'skewy':
				$skew_data['x'] = Size_Prop_Type::generate( $this->parse_angle_value( '0deg' ) );
				$skew_data['y'] = Size_Prop_Type::generate( $this->parse_angle_value( $values[0] ?? '0deg' ) );
				break;
			default:
				return null;
		}

		return Transform_Skew_Prop_Type::generate( $skew_data );
	}

	private function parse_transform_origin( string $value ): ?array {
		return null;
	}

	private function parse_perspective( string $value ): ?array {
		$value = trim( $value );

		if ( 'none' === strtolower( $value ) ) {
			return null;
		}

		$size_data = Size_Value_Parser::parse( $value );

		if ( null === $size_data ) {
			return null;
		}

		return Transform_Prop_Type::generate( [
			'perspective' => $size_data,
		] );
	}

	private function parse_perspective_origin( string $value ): ?array {
		return null;
	}

	private function split_function_args( string $args ): array {
		$values = preg_split( self::REGEX_FUNCTION_ARGS_SPLIT, trim( $args ) );
		return array_filter( $values, fn( $v ) => '' !== trim( $v ) );
	}

	private function parse_angle_value( string $value ): array {
		$value = trim( $value );

		if ( preg_match( self::REGEX_ANGLE_VALUE_PARSING, $value, $matches ) ) {
			$size = (float) $matches[1];
			$unit = strtolower( $matches[2] ?? 'deg' );

			return [
				'size' => $size,
				'unit' => $unit,
			];
		}

		return [
			'size' => 0.0,
			'unit' => 'deg',
		];
	}

	private function parse_size_or_zero( string $value ): array {
		$parsed = Size_Value_Parser::parse( $value );

		if ( null !== $parsed ) {
			return $parsed;
		}

		return $this->create_zero_size();
	}

	private function create_zero_size(): array {
		return [
			'size' => 0.0,
			'unit' => 'px',
		];
	}
}

