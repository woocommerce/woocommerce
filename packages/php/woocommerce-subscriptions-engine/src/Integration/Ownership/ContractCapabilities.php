<?php
/**
 * ContractCapabilities - the `read_subscription_contract` and `manage_subscription_contract` meta capabilities.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership;

use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;

defined( 'ABSPATH' ) || exit;

/**
 * Maps the contract capabilities: the contract's customer needs `read`, anyone else
 * `manage_woocommerce`. Checked as `current_user_can( $capability, $contract )` with a
 * `ContractView`; anything else is refused. The mapping runs first (priority 0), so extensions
 * adjust each capability with `map_meta_cap` at the default priority, or with `user_has_cap`.
 */
final class ContractCapabilities {

	/**
	 * Read one contract and the actions available for it.
	 */
	public const READ = 'read_subscription_contract';

	/**
	 * Manage one contract: run actions on it.
	 */
	public const MANAGE = 'manage_subscription_contract';

	/**
	 * Hook the mapping ahead of other `map_meta_cap` filters, so theirs build on it.
	 */
	public static function register_hooks(): void {
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 0, 4 );
	}

	/**
	 * Map the meta capability to the primitive capabilities the user needs.
	 *
	 * @param mixed $caps    Primitive capabilities so far.
	 * @param mixed $cap     Capability being checked.
	 * @param mixed $user_id User id.
	 * @param mixed $args    Extra `current_user_can()` arguments; the first is the contract.
	 * @return mixed
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( self::READ !== $cap && self::MANAGE !== $cap ) {
			return $caps;
		}

		$contract = is_array( $args ) ? ( $args[0] ?? null ) : null;
		if ( ! $contract instanceof ContractView ) {
			return array( 'do_not_allow' );
		}

		$customer_id = $contract->get_customer_id();
		if ( null !== $customer_id && $customer_id > 0 && Coercion::coerce_int( $user_id ) === $customer_id ) {
			return array( 'read' );
		}

		return array( 'manage_woocommerce' );
	}
}
