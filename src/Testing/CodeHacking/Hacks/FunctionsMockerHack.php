<?php

namespace Automattic\WooCommerce\Testing\CodeHacking\Hacks;

use ReflectionMethod;
use ReflectionClass;

/**
 * Hack to mock standalone functions.
 *
 * Usage:
 *
 * 1. Create a mock class that contains public static methods with the same
 *    names and signatures as the functions you want to mock.
 *
 * 2. Pass a 'new FunctionsMockerHack(mock_class_name)' to CodeHacker.
 */
final class FunctionsMockerHack extends CodeHack {

	private static $non_global_function_tokens = array(
		T_PAAMAYIM_NEKUDOTAYIM,
		T_DOUBLE_COLON,
		T_OBJECT_OPERATOR,
		T_FUNCTION,
	);

	/**
	 * FunctionsMockerHack constructor.
	 *
	 * @param string $mock_class Name of the class containing function mocks as public static methods.
	 * @throws ReflectionException
	 */
	public function __construct( string $mock_class ) {
		$this->mock_class = $mock_class;

		$rc = new ReflectionClass( $mock_class );

		$static_methods       = $rc->getMethods( ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC );
		$this->mocked_methods = array_map(
			function( $item ) {
				return $item->getName();
			},
			$static_methods
		);
	}

	public function hack( $code, $path ) {
		$tokens = $this->tokenize( $code );
		$code   = '';
		$previous_token_is_non_global_function_qualifier = false;

		foreach ( $tokens as $token ) {
			$token_type = $this->token_type_of( $token );
			if ( T_WHITESPACE === $token_type ) {
				$code .= $this->token_to_string( $token );
			} elseif ( T_STRING === $token_type && ! $previous_token_is_non_global_function_qualifier && in_array( $token[1], $this->mocked_methods ) ) {
				$code .= "{$this->mock_class}::{$token[1]}";
			} else {
				$code .= $this->token_to_string( $token );
				$previous_token_is_non_global_function_qualifier = in_array( $token_type, self::$non_global_function_tokens );
			}
		}

		return $code;
	}
}
