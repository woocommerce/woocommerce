<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\PublicAccess;

/**
 * Always throws, used to exercise the resolver's exception → GraphQL error
 * translation path. The argument selects which exception variety to raise.
 */
#[Name( 'failing' )]
#[Description( 'Always throws an exception' )]
#[PublicAccess]
class FailingQuery {
	public function execute(
		#[Description( 'What kind of failure to raise' )]
		string $kind = 'invalid_argument',
	): string {
		switch ( $kind ) {
			case 'api_exception':
				throw new ApiException( 'Custom failure.', 'CUSTOM_FAILURE', array( 'detail' => 'extra' ), 418 );
			case 'invalid_argument':
				throw new \InvalidArgumentException( 'Bad input from caller.' );
			case 'runtime':
			default:
				throw new \RuntimeException( 'Something blew up.' );
		}
	}
}
