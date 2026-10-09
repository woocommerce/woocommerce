<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Feature orchestrator for the POS staff + attribution iteration.
 *
 * Gates the feature on the dev-only `point_of_sale_staff` flag. The runtime surfaces —
 * staff REST endpoint, order/coupon attribution hooks, and the wp-admin Staff UI —
 * register themselves here as they are added in follow-up changes; until then on_init()
 * is an intentional no-op even when the flag is on.
 *
 * @since 11.0.0
 * @internal
 */
class POSController implements RegisterHooksInterface {

	private const FEATURE_FLAG = 'point_of_sale_staff';

	/**
	 * Features controller used to gate hook registration on the POS feature flags.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller The features controller.
	 */
	final public function init( FeaturesController $features_controller ): void {
		$this->features_controller = $features_controller;
	}

	/**
	 * Register the feature surface.
	 *
	 * The feature-flag check is deferred to `on_init` because `feature_is_enabled()`
	 * walks `FeaturesController::init_feature_definitions()`, which contains
	 * `__( ..., 'woocommerce' )` calls. Evaluating those before `init` triggers
	 * WP 6.7's "translation loading … too early" notice (and the headers-already-sent
	 * cascade that follows).
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Wire up the feature surface once translations are safe to load.
	 *
	 * No-op when the gating flag is off. Runtime surfaces are registered here
	 * as they are added in follow-up changes.
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 */
	public function on_init(): void {
		if ( ! $this->features_controller->feature_is_enabled( self::FEATURE_FLAG ) ) {
			return;
		}

		// Runtime surfaces (staff REST endpoint, attribution hooks, admin Staff UI)
		// register here as they are added in follow-up changes.
	}
}
