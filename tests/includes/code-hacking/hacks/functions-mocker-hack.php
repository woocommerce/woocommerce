<?php

require_once __DIR__ . '/code-hack.php';

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
		$tokens                            = $this->tokenize( $code );
		$code                              = '';
		$previous_token_is_object_operator = false;

		foreach ( $tokens as $token ) {
			if ( $this->is_token_of_type( $token, T_STRING ) && ! $previous_token_is_object_operator && in_array( $token[1], $this->mocked_methods ) ) {
				$code .= "{$this->mock_class}::{$token[1]}";
			} else {
				$code                             .= $this->token_to_string( $token );
				$previous_token_is_object_operator = $this->is_token_of_type( $token, T_DOUBLE_COLON ) || $this->is_token_of_type( $token, T_OBJECT_OPERATOR );
			}
		}

		return $code;
	}
}
