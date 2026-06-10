<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

/**
 * POS capability model.
 *
 * POS access is defined entirely by `pos_*` capabilities granted per-user — the
 * same primitive WordPress uses for every other authorization decision. A user
 * has POS access if and only if they hold at least one of the known `pos_*`
 * capabilities (those in all_pos_capabilities(); see has_pos_access()).
 *
 * Capabilities are granted per-user via add_cap(), never bundled onto a WP role.
 * POS access can therefore be added to any existing user (shop_manager,
 * administrator, …) without altering their role, and revoked without leaving
 * them roleless.
 *
 * The preset layer — which `pos_*` caps a Cashier / Manager / Admin receives, and
 * the code that assigns them per user — is added separately.
 *
 * @since 11.0.0
 * @internal
 */
class Capabilities {

	/**
	 * Default WP role for brand-new POS-only accounts.
	 *
	 * POS access is keyed on `pos_*` capabilities, not this role (see
	 * has_pos_access()), so new POS-only accounts use the stock `subscriber` role.
	 * A dedicated `pos_staff` role is planned for a later iteration.
	 */
	public const DEFAULT_STAFF_ROLE = 'subscriber';

	/**
	 * POS capability identifiers.
	 *
	 * Real WP capabilities, granted per-user via add_cap() when POS access is
	 * assigned. They surface in current_user_can() and the standard /wp/v2/users
	 * response — no shadow permission store.
	 */
	public const CAP_PROCESS_SALES  = 'pos_process_sales';
	public const CAP_VIEW_ORDERS    = 'pos_view_orders';
	public const CAP_APPLY_COUPONS  = 'pos_apply_coupons';
	public const CAP_CREATE_COUPONS = 'pos_create_coupons';
	public const CAP_ISSUE_REFUNDS  = 'pos_issue_refunds';
	public const CAP_VIEW_SETTINGS  = 'pos_view_settings';
	public const CAP_EDIT_SETTINGS  = 'pos_edit_settings';
	public const CAP_MANAGE_STAFF   = 'pos_manage_staff';
	public const CAP_EXIT_POS       = 'pos_exit';

	/**
	 * All known POS capability identifiers.
	 *
	 * The canonical list of `pos_*` caps — used to test for POS access and, by the
	 * preset layer, to apply or clear a user's caps as a set.
	 *
	 * @return string[]
	 */
	public static function all_pos_capabilities(): array {
		return array(
			self::CAP_PROCESS_SALES,
			self::CAP_VIEW_ORDERS,
			self::CAP_APPLY_COUPONS,
			self::CAP_CREATE_COUPONS,
			self::CAP_ISSUE_REFUNDS,
			self::CAP_VIEW_SETTINGS,
			self::CAP_EDIT_SETTINGS,
			self::CAP_MANAGE_STAFF,
			self::CAP_EXIT_POS,
		);
	}

	/**
	 * Whether a user has any POS access at all.
	 *
	 * True if the user holds at least one of the known `pos_*` capabilities (those
	 * in all_pos_capabilities()). This is the single authorization signal for POS
	 * access: neither a WP role nor any meta value grants it on its own. The
	 * any-cap definition fits both fixed presets
	 * (each preset's caps granted as a bundle) and a future granular model
	 * (individual `pos_*` caps assigned without a baseline cap).
	 *
	 * @param int $user_id Target user.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function has_pos_access( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		foreach ( self::all_pos_capabilities() as $cap ) {
			if ( user_can( $user_id, $cap ) ) {
				return true;
			}
		}
		return false;
	}
}
