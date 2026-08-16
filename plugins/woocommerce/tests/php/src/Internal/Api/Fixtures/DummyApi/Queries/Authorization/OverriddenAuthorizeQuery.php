<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Authorization;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Inheritance\BaseManageOptionsQuery;

/**
 * Inherits #[RequiredCapability('manage_options')] from its parent and
 * declares its own authorize(). This is the documented override mechanism:
 * authorize() takes precedence, the inherited cap is silently superseded
 * (no $_preauthorized parameter, so no composition).
 */
#[Name( 'overriddenAuthorize' )]
#[Description( 'authorize() supersedes the cap inherited from the parent' )]
class OverriddenAuthorizeQuery extends BaseManageOptionsQuery {
	public function execute(): string {
		return 'authorize wins';
	}

	/**
	 * Allow only callers with the edit_posts capability — independent of
	 * the manage_options cap inherited from {@see BaseManageOptionsQuery}.
	 */
	public function authorize(): bool {
		return current_user_can( 'edit_posts' );
	}
}
