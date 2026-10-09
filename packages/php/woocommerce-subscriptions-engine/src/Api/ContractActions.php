<?php
/**
 * ContractActions - register the contract actions the engine's action endpoint dispatches.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use Automattic\WooCommerce\SubscriptionsEngine\Integration\Rest\ContractActionRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Public registration facade for contract actions.
 *
 * `wc/v3/subscriptions-engine/contracts/{id}/action` dispatches only to actions registered by
 * the contract's owning extension. The engine registers no actions; the permission presets
 * are opt-in. Register on or before `rest_api_init`. Final and static-only.
 */
final class ContractActions {

	/**
	 * Keys accepted by {@see self::register()}, as a key map.
	 *
	 * @var array<string, true>
	 */
	private const REGISTRATION_KEYS = array(
		'callback'     => true,
		'permission'   => true,
		'description'  => true,
		'args'         => true,
		'is_available' => true,
	);

	/**
	 * Register an action for the extension's contracts.
	 *
	 * An invalid or duplicate registration raises a `_doing_it_wrong()` notice and is not
	 * registered (the first registration is kept). Unknown keys raise a notice and are ignored.
	 *
	 * @param string               $extension_slug Owning extension slug; matches the contracts' `extension_slug`.
	 * @param string               $action         Action slug: lowercase letters, numbers, hyphens and underscores.
	 * @param array<string, mixed> $args           `callback` (required): `callable( ContractView $contract, array $action_args ): ContractView|WP_Error`.
	 *                                             `permission` (required): `'manager'` (`manage_woocommerce`), `'customer'`
	 *                                             (the contract's customer), or `callable( ContractView $contract, WP_REST_Request $request ): bool`.
	 *                                             `description` (string, default '').
	 *                                             `args`: REST property schemas for `action_args` (`array<string, array>`),
	 *                                             or `callable( ContractView $contract ): array` resolved per request.
	 *                                             `is_available`: `callable( ContractView $contract ): bool`, default always.
	 */
	public static function register( string $extension_slug, string $action, array $args ): void {
		$filtered_args = ArgumentValidator::filter_known_keys( __METHOD__, $args, self::REGISTRATION_KEYS, 'argument' );
		$callback      = $filtered_args['callback'] ?? null;
		$permission    = $filtered_args['permission'] ?? null;
		$description   = $filtered_args['description'] ?? '';
		$action_args   = $filtered_args['args'] ?? array();
		$is_available  = $filtered_args['is_available'] ?? null;

		if ( '' === trim( $extension_slug ) ) {
			self::reject( 'The extension slug must not be empty.' );
			return;
		}

		if ( 1 !== preg_match( '/^[a-z0-9_-]+$/', $action ) ) {
			self::reject( sprintf( 'Contract action "%s" may only contain lowercase letters, numbers, hyphens and underscores.', $action ) );
			return;
		}

		if ( ! is_callable( $callback ) ) {
			self::reject( sprintf( 'Contract action "%s" needs a callable "callback".', $action ) );
			return;
		}

		if ( ! in_array( $permission, array( ContractActionRegistry::PERMISSION_MANAGER, ContractActionRegistry::PERMISSION_CUSTOMER ), true ) && ! is_callable( $permission ) ) {
			self::reject( sprintf( 'Contract action "%s" needs a "permission" of "manager", "customer" or a callable.', $action ) );
			return;
		}

		if ( ! is_string( $description ) ) {
			self::reject( sprintf( 'The "description" of contract action "%s" must be a string.', $action ) );
			return;
		}

		if ( ! is_callable( $action_args ) && ! ContractActionRegistry::is_args_schema( $action_args ) ) {
			self::reject( sprintf( 'The "args" of contract action "%s" must be an array of property schemas or a callable.', $action ) );
			return;
		}

		if ( null !== $is_available && ! is_callable( $is_available ) ) {
			self::reject( sprintf( 'The "is_available" of contract action "%s" must be a callable.', $action ) );
			return;
		}

		if ( ContractActionRegistry::has( $extension_slug, $action ) ) {
			self::reject( sprintf( 'Contract action "%s" is already registered for "%s"; the first registration is kept.', $action, $extension_slug ) );
			return;
		}

		ContractActionRegistry::add(
			array(
				'extension_slug' => $extension_slug,
				'action'         => $action,
				'callback'       => $callback,
				'permission'     => $permission,
				'description'    => $description,
				'args'           => $action_args,
				'is_available'   => $is_available,
			)
		);
	}

	/**
	 * Raise the `_doing_it_wrong()` notice for a registration that is not registered.
	 *
	 * @param string $message What is wrong.
	 */
	private static function reject( string $message ): void {
		_doing_it_wrong( __CLASS__ . '::register', esc_html( $message ), '0.0.1' );
	}
}
