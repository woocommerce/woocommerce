<?php

namespace Automattic\WooCommerce\Tests\RemoteSpecsValidation\Tests;

use Automattic\WooCommerce\Tests\RemoteSpecsValidation\RemoteSpecValidator;

class RemoteSpecValidatorTest extends TestCase {
	public function test_it_throws_invalid_argument_exception_with_invalid_bundle() {
		$this->expectException( \InvalidArgumentException::class);
		RemoteSpecValidator::create_from_bundle( 'invalid-bundle' );
	}

	public function test_it_returns_formatted_errors() {
	    $validator = RemoteSpecValidator::create_from_bundle( 'remote-inbox-notification' );
		$result = $validator->validate( '{}' );
		$errors = $result->get_errors();
		$this->assertIsArray( $errors );
		$this->assertEquals("/", array_keys( $errors )[0] );
	}
}
