<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Authorization;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Infrastructure\Principal;

/**
 * Exercises the `_principal` infrastructure parameter on both authorize() and
 * execute().
 *
 * Returns the principal's user_login when authenticated, or 'anonymous' when
 * not (the underlying WP_User has ID === 0). Authorize() returns true
 * unconditionally — the test isn't gating access, just verifying the principal
 * flows through the typed channel.
 */
#[Name( 'principalAware' )]
#[Description( 'Echoes the principal user_login (or "anonymous").' )]
class PrincipalAwareQuery {
	public function execute( Principal $_principal ): string {
		return $_principal->is_authenticated() ? $_principal->user->user_login : 'anonymous';
	}

	/**
	 * Authorize the call. Always allows; the test reads the value out via execute().
	 *
	 * @param Principal $_principal The resolved principal.
	 */
	public function authorize( Principal $_principal ): bool {
		unset( $_principal );
		return true;
	}
}
