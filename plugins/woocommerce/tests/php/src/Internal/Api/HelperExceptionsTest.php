<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\ForbiddenException;
use Automattic\WooCommerce\Api\InvalidTokenException;
use Automattic\WooCommerce\Api\NotFoundException;
use Automattic\WooCommerce\Api\UnauthorizedException;
use Automattic\WooCommerce\Api\ValidationException;
use WC_Unit_Test_Case;

/**
 * Tests for the helper ApiException subclasses, each pinning a specific
 * (error code, HTTP status) pair so callers don't have to spell them out at
 * the throw site.
 *
 * The actual code → status mapping that turns these into HTTP responses lives
 * in {@see \Automattic\WooCommerce\Api\Infrastructure\GraphQLControllerBase} and is
 * exercised end-to-end via {@see SecurityTest::test_invalid_token_error_code_maps_to_401()}
 * and similar; this file just verifies the exception classes themselves carry
 * the right metadata.
 */
class HelperExceptionsTest extends WC_Unit_Test_Case {
	/**
	 * @return array<string, array{class-string, string, int}>
	 */
	public function provider_helper_exceptions(): array {
		return array(
			'unauthorized'  => array( UnauthorizedException::class, 'UNAUTHORIZED', 401 ),
			'invalid_token' => array( InvalidTokenException::class, 'INVALID_TOKEN', 401 ),
			'forbidden'     => array( ForbiddenException::class, 'FORBIDDEN', 403 ),
			'not_found'     => array( NotFoundException::class, 'NOT_FOUND', 404 ),
			'validation'    => array( ValidationException::class, 'VALIDATION_ERROR', 422 ),
		);
	}

	/**
	 * @testdox each helper extends ApiException and pins the expected code and HTTP status.
	 *
	 * @dataProvider provider_helper_exceptions
	 *
	 * @param class-string $class       The helper exception class.
	 * @param string       $code        The expected error code.
	 * @param int          $status_code The expected HTTP status code.
	 */
	public function test_helper_exception_carries_code_and_status( string $class, string $code, int $status_code ): void {
		$exception = new $class();

		$this->assertInstanceOf( ApiException::class, $exception );
		$this->assertSame( $code, $exception->getErrorCode() );
		$this->assertSame( $status_code, $exception->getStatusCode() );
		$this->assertNotEmpty( $exception->getMessage() );
		$this->assertSame( array(), $exception->getExtensions() );
	}

	/**
	 * @testdox each helper accepts a custom message, extensions, and previous throwable.
	 *
	 * @dataProvider provider_helper_exceptions
	 *
	 * @param class-string $class The helper exception class.
	 */
	public function test_helper_exception_accepts_custom_args( string $class ): void {
		$previous = new \RuntimeException( 'inner' );

		$exception = new $class( 'Custom message.', array( 'detail' => 'extra' ), $previous );

		$this->assertSame( 'Custom message.', $exception->getMessage() );
		$this->assertSame( array( 'detail' => 'extra' ), $exception->getExtensions() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}
}
