<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\POS\Admin\UserFormIntegration;
use Automattic\WooCommerce\Internal\POS\Auth\POSAuthHandler;
use Automattic\WooCommerce\Internal\POS\Auth\POSCapBridge;
use Automattic\WooCommerce\Internal\POS\CouponAttribution;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Feature orchestrator for the POS staff + attribution iteration.
 *
 * Gates the feature on the dev-only `point_of_sale_staff` flag. When on, wires up the server-side
 * staff auth (current-user swap + capability bridge), the staff REST endpoint, the order/coupon
 * attribution lifecycle hooks, and the wp-admin Add New User integration via the DI container.
 *
 * @since 11.0.0
 * @internal
 */
class POSController implements RegisterHooksInterface {

	public const FEATURE_FLAG = 'point_of_sale_staff';

	/**
	 * Features controller used to gate hook registration on the POS feature flags.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * REST controller for the /wc/pos/v1/staff endpoint.
	 *
	 * @var POSStaffController
	 */
	private POSStaffController $staff_controller;

	/**
	 * Order attribution lifecycle handler.
	 *
	 * @var OrderAttribution
	 */
	private OrderAttribution $order_attribution;

	/**
	 * Coupon attribution lifecycle handler.
	 *
	 * @var CouponAttribution
	 */
	private CouponAttribution $coupon_attribution;

	/**
	 * Wp-admin Add New User form integration.
	 *
	 * @var UserFormIntegration
	 */
	private UserFormIntegration $user_form_integration;

	/**
	 * Server-side POS staff auth handler (current-user swap).
	 *
	 * @var POSAuthHandler
	 */
	private POSAuthHandler $auth_handler;

	/**
	 * POS-scoped capability bridge.
	 *
	 * @var POSCapBridge
	 */
	private POSCapBridge $cap_bridge;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param FeaturesController  $features_controller   The features controller.
	 * @param POSStaffController  $staff_controller      The staff REST controller.
	 * @param OrderAttribution    $order_attribution     The order attribution lifecycle handler.
	 * @param CouponAttribution   $coupon_attribution    The coupon attribution lifecycle handler.
	 * @param UserFormIntegration $user_form_integration The Add New User form integration.
	 * @param POSAuthHandler      $auth_handler          The server-side staff auth handler.
	 * @param POSCapBridge        $cap_bridge            The POS-scoped capability bridge.
	 */
	final public function init(
		FeaturesController $features_controller,
		POSStaffController $staff_controller,
		OrderAttribution $order_attribution,
		CouponAttribution $coupon_attribution,
		UserFormIntegration $user_form_integration,
		POSAuthHandler $auth_handler,
		POSCapBridge $cap_bridge
	): void {
		$this->features_controller   = $features_controller;
		$this->staff_controller      = $staff_controller;
		$this->order_attribution     = $order_attribution;
		$this->coupon_attribution    = $coupon_attribution;
		$this->user_form_integration = $user_form_integration;
		$this->auth_handler          = $auth_handler;
		$this->cap_bridge            = $cap_bridge;
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
	 * @since 11.0.0
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );

		// Defensive: flush rewrite rules when the gating flag is flipped, so any host
		// where the wp-json catch-all rewrite is stale (managed hosting, page-cache
		// plugins, partial wp-env state) doesn't 404 the new /wc/pos/* routes after
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
	 * @since 11.0.0
	 */
	public function handle_flag_option_changed(): void {
		add_action(
			'shutdown',
			static function () {
				WC()->call_function( 'flush_rewrite_rules', false );
			}
		);
	}

	/**
	 * Wire up the feature surface once translations are safe to load.
	 *
	 * No-op when the gating flag is off.
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 */
	public function on_init(): void {
		if ( ! $this->features_controller->feature_is_enabled( self::FEATURE_FLAG ) ) {
			return;
		}

		// Server-side staff auth must register first: the current-user swap hooks onto
		// determine_current_user, and the capability bridge onto user_has_cap.
		$this->auth_handler->register();
		$this->cap_bridge->register();

		$this->staff_controller->register();
		$this->order_attribution->register();
		$this->coupon_attribution->register();
		$this->user_form_integration->register();
	}
}
