<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Infrastructure;

/**
 * Default principal class for the WooCommerce dual code+GraphQL API.
 *
 * Plugins that authenticate against something other than WordPress users must ship
 * their own principal class at `<plugin-api-namespace>\Infrastructure\...` together
 * with a matching `PrincipalResolver`. Plugins that build on WP-user auth can either
 * use this class directly (no resolver needed; the controller falls back to
 * `new Principal( wp_get_current_user() )`) or extend it to add their own
 * fields.
 */
class Principal {
	/**
	 * Constructor.
	 *
	 * @param \WP_User $user The WordPress user behind the request. For anonymous requests this is a `WP_User` with `ID === 0`, as returned by {@see \wp_get_current_user()}.
	 */
	public function __construct(
		public readonly \WP_User $user,
	) {
	}

	/**
	 * Whether the underlying WP user is authenticated.
	 *
	 * Convenience for `$principal->user->ID > 0`, the canonical anonymous
	 * marker in WordPress. Use this in `authorize()` / `execute()` bodies that
	 * need to distinguish anonymous from authenticated callers.
	 */
	public function is_authenticated(): bool {
		return $this->user->ID > 0;
	}
}
