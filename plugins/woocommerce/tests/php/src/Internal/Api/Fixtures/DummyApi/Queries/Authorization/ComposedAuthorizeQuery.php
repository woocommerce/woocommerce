<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries\Authorization;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;

/**
 * Composes a #[RequiredCapability] with a custom authorize(): the resolver
 * passes the cap-check result as the `$_preauthorized` infrastructure
 * argument, so this method can either short-circuit on the attribute's
 * decision or fall back to its own logic (here: an extra cap fallback).
 */
#[Name( 'composedAuthorize' )]
#[Description( 'Composes #[RequiredCapability] with authorize() via $_preauthorized' )]
#[RequiredCapability( 'manage_options' )]
class ComposedAuthorizeQuery {
	public function execute(): string {
		return 'composed';
	}

	/**
	 * Allow when the attribute already passed (preauthorized) OR when the
	 * caller has the edit_posts fallback cap.
	 *
	 * @param bool $_preauthorized True when current_user_can('manage_options') passed at the resolver level.
	 */
	public function authorize( bool $_preauthorized ): bool {
		return $_preauthorized || current_user_can( 'edit_posts' );
	}
}
