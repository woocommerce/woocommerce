<?php

namespace Automattic\WooCommerce\Testing\CodeHacking\Hacks;

use ReflectionMethod;
use ReflectionClass;

/**
 * Hack to mock public static methods and properties.
 *
 * How to use:
 *
 * 1. Create a mock class that contains public static methods and properties with the same
 *    names and signatures as the ones in the class you want to mock.
 *
 * 2. Pass a 'new StaticMockerHack(original_class_name, mock_class_name)' to CodeHacker.
 *
 * Invocations of public static members from the original class that exist in the mock class
 * will be replaced with invocations of the same members for the mock class.
 * Invocations of members not existing in the mock class will be left unmodified.
 */
final class StaticMockerHack extends CodeHack {

	/**
	 * StaticMockerHack constructor.
	 *
	 * @param string $source_class Name of the original class (the one having the members to be mocked).
	 * @param string $mock_class Name of the mock class (the one having the replacement mock members).
	 * @throws ReflectionException
	 */
	public function __construct( string $source_class, string $mock_class ) {
		$this->source_class = $source_class;
		$this->target_class = $mock_class;

		$rc = new ReflectionClass( $mock_class );

		$static_methods = $rc->getMethods( ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC );
		$static_methods = array_map(
			function( $item ) {
				return $item->getName();
			},
			$static_methods
		);

		$static_properties = $rc->getProperties( ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC );
		$static_properties = array_map(
			function( $item ) {
				return '$' . $item->getName();
			},
			$static_properties
		);

		$this->members_implemented_in_mock = array_merge( $static_methods, $static_properties );
	}

	public function hack( $code, $path ) {
		$last_item = null;

		if ( stripos( $code, $this->source_class . '::' ) !== false ) {
			$tokens        = $this->tokenize( $code );
			$code          = '';
			$current_token = null;

			while ( $current_token = current( $tokens ) ) {
				if ( $this->is_token_of_type( $current_token, T_STRING ) && $this->source_class === $current_token[1] ) {
					$next_token = next( $tokens );
					if ( $this->is_token_of_type( $next_token, T_DOUBLE_COLON ) ) {
						$called_member = next( $tokens )[1];
						if ( in_array( $called_member, $this->members_implemented_in_mock ) ) {
							// Reference to source class member that exists in mock class, replace.
							$code .= "{$this->target_class}::{$called_member}";
						} else {
							// Reference to source class member that does NOT exists in mock class, leave unchanged.
							$code .= "{$this->source_class}::{$called_member}";
						}
					} else {
						// Reference to source class, but not followed by '::'.
						$code .= $this->token_to_string( $current_token ) . $this->token_to_string( $next_token );
					}
				} else {
					// Not a reference to source class.
					$code .= $this->token_to_string( $current_token );
				}
				next( $tokens );
			}
		}

		return $code;
	}
}
