<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

/**
 * POS access + capability model (Proposal 1 — user-meta-based).
 *
 * Replaces the per-role WP capability surface from the earlier draft with a single
 * user-meta key (`_pos_role`) and a static capability matrix computed server-side
 * and shipped to the mobile client. No new WP roles, no new WP capabilities.
 *
 * Why this shape:
 *  - POS role is orthogonal to the WordPress role. The merchant explicitly assigns
 *    one of three POS roles (pos_cashier / pos_manager / pos_admin) to whichever
 *    users need to use the POS app — including WP administrators and shop managers,
 *    who do NOT get implicit POS access. This avoids the "if a POS user somehow
 *    auths, they get refund_shop_orders for the whole REST API" risk raised in
 *    proposal review.
 *  - The POS cap names (`process_sales`, `issue_refunds`, …) are NOT WP caps. They
 *    are client-side gating keys served in the /staff payload. The WC REST surface
 *    is unaffected by them, so a POS user without an underlying WP role with
 *    elevated caps cannot perform privileged operations via the public REST API.
 *
 * Trade-off (accepted): wp_usermeta is broadly readable by any plugin via
 * get_user_meta() and rides along in user exports / backups. Indexing for
 * multi-condition queries (multi-location, etc.) is weaker than a dedicated table.
 * If those constraints bite, the model can be lifted into a dedicated table later
 * without changing the wire shape — only this class needs to change.
 *
 * @since 10.9.0
 * @internal
 */
class Capabilities {

	/**
	 * User meta key storing the explicit POS role assignment.
	 */
	public const POS_ROLE_META_KEY = '_pos_role';

	/**
	 * POS role identifiers.
	 *
	 * All three are explicit `_pos_role` meta values. There is no implicit POS
	 * access from a WP role — administrators and shop managers must be assigned
	 * a POS role in the Staff settings page like any other user.
	 */
	public const POS_ROLE_CASHIER = 'pos_cashier';
	public const POS_ROLE_MANAGER = 'pos_manager';
	public const POS_ROLE_ADMIN   = 'pos_admin';

	/**
	 * Client-side POS capability identifiers.
	 *
	 * These are JSON keys in the /staff payload, not WP capabilities. The mobile
	 * client gates UI by reading them from the cached staff record for the
	 * PIN-authenticated operator.
	 */
	public const CAP_PROCESS_SALES     = 'process_sales';
	public const CAP_VIEW_ORDERS       = 'view_orders';
	public const CAP_APPLY_COUPONS     = 'apply_coupons';
	public const CAP_CREATE_COUPONS    = 'create_coupons';
	public const CAP_ISSUE_REFUNDS     = 'issue_refunds';
	public const CAP_VIEW_POS_SETTINGS = 'view_pos_settings';
	public const CAP_EDIT_POS_SETTINGS = 'edit_pos_settings';
	public const CAP_MANAGE_POS_STAFF  = 'manage_pos_staff';
	public const CAP_EXIT_POS          = 'exit_pos';

	/**
	 * All assignable POS role values, in ascending capability order.
	 *
	 * @return string[]
	 */
	public static function assignable_pos_roles(): array {
		return array(
			self::POS_ROLE_CASHIER,
			self::POS_ROLE_MANAGER,
			self::POS_ROLE_ADMIN,
		);
	}

	/**
	 * Resolve the assigned POS role for a user, or null if they have none.
	 *
	 * Reads the `_pos_role` user meta value and returns it only if it matches an
	 * assignable POS role. No implicit fallback to WP role — a wp administrator
	 * without an explicit POS role assignment has no POS access.
	 *
	 * @param int $user_id Target user.
	 * @return string|null One of self::POS_ROLE_* or null.
	 *
	 * @since 10.9.0
	 */
	public static function get_pos_role( int $user_id ): ?string {
		$meta = get_user_meta( $user_id, self::POS_ROLE_META_KEY, true );
		if ( in_array( $meta, self::assignable_pos_roles(), true ) ) {
			return (string) $meta;
		}
		return null;
	}

	/**
	 * Whether a user has any POS access at all.
	 *
	 * @param int $user_id Target user.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function has_pos_access( int $user_id ): bool {
		return null !== self::get_pos_role( $user_id );
	}

	/**
	 * Assign or clear the POS role for a user.
	 *
	 * Passing null removes the meta entry — the user loses POS access entirely.
	 *
	 * @param int         $user_id  Target user.
	 * @param string|null $pos_role One of self::assignable_pos_roles(), or null.
	 * @return bool True if accepted (including clears); false if the value is
	 *              not an assignable POS role.
	 *
	 * @since 10.9.0
	 */
	public static function set_pos_role( int $user_id, ?string $pos_role ): bool {
		if ( null === $pos_role ) {
			delete_user_meta( $user_id, self::POS_ROLE_META_KEY );
			return true;
		}

		if ( ! in_array( $pos_role, self::assignable_pos_roles(), true ) ) {
			return false;
		}

		update_user_meta( $user_id, self::POS_ROLE_META_KEY, $pos_role );
		return true;
	}

	/**
	 * The client-side POS capability map for a given POS role.
	 *
	 *     Capability             Cashier   Manager   Admin
	 *     process_sales            yes       yes      yes
	 *     view_orders              yes       yes      yes
	 *     apply_coupons            yes       yes      yes
	 *     create_coupons           no        yes      yes
	 *     issue_refunds            no        yes      yes
	 *     view_pos_settings        no        yes      yes
	 *     edit_pos_settings        no        no       yes
	 *     manage_pos_staff         no        no       yes
	 *     exit_pos                 no        no       yes
	 *
	 * Override rows in the i2 proposal (cashier creating coupons, etc.) live on
	 * the override approver's role, not on the operator — i.e. a cashier never
	 * receives `create_coupons` here, even temporarily.
	 *
	 * @param string $pos_role One of self::POS_ROLE_* constants.
	 * @return array<string, true>
	 *
	 * @since 10.9.0
	 */
	public static function capabilities_for_role( string $pos_role ): array {
		$cashier = array(
			self::CAP_PROCESS_SALES => true,
			self::CAP_VIEW_ORDERS   => true,
			self::CAP_APPLY_COUPONS => true,
		);

		$manager = $cashier + array(
			self::CAP_CREATE_COUPONS    => true,
			self::CAP_ISSUE_REFUNDS     => true,
			self::CAP_VIEW_POS_SETTINGS => true,
		);

		$admin = $manager + array(
			self::CAP_EDIT_POS_SETTINGS => true,
			self::CAP_MANAGE_POS_STAFF  => true,
			self::CAP_EXIT_POS          => true,
		);

		switch ( $pos_role ) {
			case self::POS_ROLE_CASHIER:
				return $cashier;
			case self::POS_ROLE_MANAGER:
				return $manager;
			case self::POS_ROLE_ADMIN:
				return $admin;
			default:
				return array();
		}
	}

	/**
	 * Translated label for a POS role.
	 *
	 * @param string $pos_role One of self::POS_ROLE_* constants.
	 * @return string Empty string if the role is unknown.
	 *
	 * @since 10.9.0
	 */
	public static function role_label( string $pos_role ): string {
		switch ( $pos_role ) {
			case self::POS_ROLE_CASHIER:
				return __( 'POS Cashier', 'woocommerce' );
			case self::POS_ROLE_MANAGER:
				return __( 'POS Manager', 'woocommerce' );
			case self::POS_ROLE_ADMIN:
				return __( 'POS Admin', 'woocommerce' );
			default:
				return '';
		}
	}
}
