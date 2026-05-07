<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Authorization;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\InvalidTokenException;

/**
 * Authorization is decided solely by `authorize()`, which always throws. The
 * `$kind` argument selects the exception class so tests can verify the
 * resolver's exception-translation path for each (an `ApiException` carries
 * its custom code through; any other `Throwable` is masked behind
 * `INTERNAL_ERROR` with a generic message).
 */
#[Name( 'authorizeThrows' )]
#[Description( 'authorize() throws to verify exception translation' )]
class AuthorizeThrowsQuery {
	public function execute(
		#[Description( 'Which exception class authorize() should raise.' )]
		string $kind,
	): string {
		// Never reached — authorize() always throws.
		unset( $kind );
		return 'unreachable';
	}

	/**
	 * Always throws. The `$kind` argument selects the exception class.
	 *
	 * @param string $kind Exception variety to raise.
	 *
	 * @throws ApiException     When `$kind === 'api_exception'`.
	 * @throws \RuntimeException Otherwise.
	 */
	public function authorize( string $kind ): bool {
		if ( 'api_exception' === $kind ) {
			throw new ApiException( 'Authorize failed.', 'AUTH_FAILURE', array( 'detail' => 'extra' ), 403 );
		}
		if ( 'invalid_token' === $kind ) {
			throw new InvalidTokenException();
		}
		throw new \RuntimeException( 'Internals leaked from authorize.' );
	}
}
