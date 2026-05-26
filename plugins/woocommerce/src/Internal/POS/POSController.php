<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Feature orchestrator for the POS staff + attribution iteration.
 *
 * Short-circuits on either the parent `point_of_sale` feature being off or the
 * dev-only `point_of_sale_staff` sub-flag being off. When both are on, wires up
 * the staff REST endpoint and the order-attribution lifecycle hooks via the DI
 * container.
 *
 * @since 10.9.0
 * @internal
 */
class POSController implements RegisterHooksInterface {

	public const FEATURE_FLAG = 'point_of_sale_staff';
	public const PARENT_FLAG  = 'point_of_sale';

	/**
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * @var POSStaffController
	 */
	private POSStaffController $staff_controller;

	/**
	 * @var OrderAttribution
	 */
	private OrderAttribution $order_attribution;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller The features controller.
	 * @param POSStaffController $staff_controller    The staff REST controller.
	 * @param OrderAttribution   $order_attribution   The order attribution lifecycle handler.
	 */
	final public function init(
		FeaturesController $features_controller,
		POSStaffController $staff_controller,
		OrderAttribution $order_attribution
	): void {
		$this->features_controller = $features_controller;
		$this->staff_controller    = $staff_controller;
		$this->order_attribution   = $order_attribution;
	}

	/**
	 * Register the feature surface.
	 *
	 * The feature-flag check is deferred to `init` because `feature_is_enabled()`
	 * walks `FeaturesController::init_feature_definitions()`, which contains
	 * `__( ..., 'woocommerce' )` calls. Evaluating those before `init` triggers
	 * WP 6.7's "translation loading … too early" notice (and the headers-already-sent
	 * cascade that follows).
	 *
	 * @since 10.9.0
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );

		// Defensive: flush rewrite rules when the gating flag is flipped, so any host
		// where the wp-json catch-all rewrite is stale (managed hosting, page-cache
		// plugins, partial wp-env state) doesn't 404 the new /wc-pos/* routes after
		// the feature is enabled. REST dispatch normally doesn't depend on per-route
		// rewrites, but the global wp-json rule must exist for any REST URL to work.
		$flag_option = 'woocommerce_feature_' . self::FEATURE_FLAG . '_enabled';
		add_action( 'add_option_' . $flag_option, array( $this, 'handle_flag_option_changed' ) );
		add_action( 'update_option_' . $flag_option, array( $this, 'handle_flag_option_changed' ) );
	}

	/**
	 * Schedule a rewrite-rule flush when the POS staff feature flag is toggled.
	 *
	 * Hooked on add_option_/update_option_ for the gating flag. Uses the standard
	 * "schedule on shutdown" pattern so the flush doesn't run mid-admin-request
	 * (which would slow the response and potentially clobber a redirect).
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	public function handle_flag_option_changed(): void {
		add_action(
			'shutdown',
			static function () {
				flush_rewrite_rules( false );
			}
		);
	}

	/**
	 * Wire up the feature surface once translations are safe to load.
	 *
	 * No-op when either gating flag is off.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	public function on_init(): void {
		// Always wired: the POS roles are registered unconditionally on upgrade so the
		// flag flip stays a pure config toggle, but we hide them from the role selector
		// (Users -> Add New / Edit User / Bulk Edit) whenever the feature is off so
		// admins on opt-out sites don't see roles for a feature they aren't using.
		add_filter( 'editable_roles', array( $this, 'handle_editable_roles' ) );

		if ( ! $this->features_controller->feature_is_enabled( self::PARENT_FLAG ) ) {
			return;
		}
		if ( ! $this->features_controller->feature_is_enabled( self::FEATURE_FLAG ) ) {
			return;
		}

		$this->staff_controller->register();
		$this->order_attribution->register();

		add_action( 'admin_init', array( $this, 'handle_admin_init_block_pos_only_roles' ) );
	}

	/**
	 * Hide the POS roles from the role selector when the feature is disabled.
	 *
	 * The roles live in `wp_options['wp_user_roles']` regardless of flag state
	 * (so flipping the flag back on doesn't need a migration), but exposing them
	 * in the standard WP role dropdown on opt-out sites is confusing — admins see
	 * "POS Cashier" / "POS Manager" choices for a feature they haven't enabled.
	 * This filter strips them from any screen that calls `get_editable_roles()`.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 *
	 * @param array<string, array<string, mixed>> $roles The editable roles list.
	 * @return array<string, array<string, mixed>>
	 */
	public function handle_editable_roles( array $roles ): array {
		$flag_on = $this->features_controller->feature_is_enabled( self::PARENT_FLAG )
			&& $this->features_controller->feature_is_enabled( self::FEATURE_FLAG );

		if ( $flag_on ) {
			return $roles;
		}

		unset( $roles[ Capabilities::ROLE_CASHIER ], $roles[ Capabilities::ROLE_MANAGER ] );

		return $roles;
	}

	/**
	 * Bounce POS-only roles (pos_cashier, pos_manager) away from wp-admin.
	 *
	 * The i2 proposal frames the POS roles as "access only within POS, not within
	 * wp-admin." WordPress's default behavior still lets any user with the `read`
	 * cap reach wp-admin (dashboard, profile.php). This handler closes that gap by
	 * redirecting any user that holds `view_pos` but not `manage_woocommerce` —
	 * which is exactly pos_cashier + pos_manager — back to the site front-end.
	 *
	 * Admins and shop_managers (who hold both `view_pos` and `manage_woocommerce`)
	 * are left untouched. AJAX/cron/REST contexts are excluded so legitimate
	 * back-channel calls keep working.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	public function handle_admin_init_block_pos_only_roles(): void {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! current_user_can( 'view_pos' ) ) {
			return;
		}

		wp_safe_redirect( home_url() );
		exit;
	}
}
