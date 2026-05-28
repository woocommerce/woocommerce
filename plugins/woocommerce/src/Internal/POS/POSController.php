<?php
/**
 * POSController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\POS\RestApi\POSStaffController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps the Point of Sale staff (actors) feature: capability grants,
 * cascade hooks for WP user deletion, schema creation on feature-flag toggle,
 * and REST controller registration.
 *
 * All wiring is gated on the `point_of_sale_actors` feature flag — when the
 * flag is off, this controller registers nothing.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class POSController implements RegisterHooksInterface {

	public const FEATURE_FLAG = 'point_of_sale_actors';

	/**
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * @var ActorRepository
	 */
	private ActorRepository $actor_repository;

	/**
	 * @var POSStaffController
	 */
	private POSStaffController $staff_controller;

	/**
	 * @var OrderAttribution
	 */
	private OrderAttribution $order_attribution;

	/**
	 * DI init.
	 *
	 * @internal
	 *
	 * @param FeaturesController  $features_controller Features controller.
	 * @param ActorRepository     $actor_repository    Actor repository.
	 * @param POSStaffController $staff_controller   Staff REST controller.
	 * @param OrderAttribution    $order_attribution   Order attribution validator.
	 * @return void
	 */
	final public function init(
		FeaturesController $features_controller,
		ActorRepository $actor_repository,
		POSStaffController $staff_controller,
		OrderAttribution $order_attribution
	): void {
		$this->features_controller = $features_controller;
		$this->actor_repository    = $actor_repository;
		$this->staff_controller   = $staff_controller;
		$this->order_attribution   = $order_attribution;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * The feature-flag check itself triggers `__()` calls inside the
	 * FeaturesController feature list (the flag's `name`/`description`
	 * translate the woocommerce textdomain), so it has to run AFTER the
	 * `init` action — otherwise WP 6.7+ logs a
	 * `_load_textdomain_just_in_time was called incorrectly` notice which
	 * breaks header output. We register the option-toggle handler eagerly
	 * (no translations involved) and defer everything else to `init`.
	 *
	 * @return void
	 */
	public function register(): void {
		// Toggle handlers are safe to register eagerly — they only reference an option name.
		// WP fires `add_option_*` on first-time creation and `update_option_*` on subsequent
		// changes, so both are needed to catch the initial enable.
		$option_name = 'woocommerce_feature_' . self::FEATURE_FLAG . '_enabled';
		add_action( 'add_option_' . $option_name, array( $this, 'handle_feature_flag_added' ), 10, 2 );
		add_action( 'update_option_' . $option_name, array( $this, 'handle_feature_flag_toggle' ), 10, 2 );

		add_action( 'init', array( $this, 'register_feature_hooks' ), 0 );
	}

	/**
	 * Register the feature-gated hooks. Runs on `init` so any translation
	 * calls (FeaturesController feature-list lookup, etc.) happen after the
	 * textdomain is safely loadable.
	 *
	 * @return void
	 */
	public function register_feature_hooks(): void {
		if ( ! $this->features_controller->feature_is_enabled( self::FEATURE_FLAG ) ) {
			return;
		}

		// Cascade: when a WP user is deleted, detach them from any linked actor.
		add_action( 'deleted_user', array( $this, 'handle_deleted_user' ), 10, 1 );

		// REST routes (M1: GET /wc/pos/v1/staff only). Auto-wires via WC's REST namespace filter.
		$this->staff_controller->register();

		// Order attribution validation + post-insert logging.
		$this->order_attribution->register();
	}

	/**
	 * Handle first-time creation of the feature flag option (no prior row in
	 * wp_options). WP fires `add_option_*` rather than `update_option_*` in
	 * this case, so we trampoline to the same install routine.
	 *
	 * @param string $option Option name (unused; bound by hook).
	 * @param mixed  $value  New option value ("yes" or "no").
	 * @return void
	 */
	public function handle_feature_flag_added( $option, $value ): void {
		unset( $option );
		if ( 'yes' === $value ) {
			$this->run_feature_install();
		}
	}

	/**
	 * Handle the feature flag option change. When the flag flips from
	 * "no" → "yes", run the install routine so the actor tables get created
	 * via dbDelta and the POS capabilities get granted to existing roles.
	 * Idempotent.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public function handle_feature_flag_toggle( $old_value, $new_value ): void {
		if ( 'yes' !== $new_value || 'yes' === $old_value ) {
			return;
		}
		$this->run_feature_install();
	}

	/**
	 * Install routine triggered when the flag transitions to "yes": dbDelta
	 * the schema. Idempotent — dbDelta is a no-op for unchanged tables.
	 *
	 * The wp-admin Staff page and the staff REST endpoint are gated on the
	 * existing `manage_woocommerce` capability (already granted to admin
	 * and shop_manager by default), so no new capability is registered or
	 * granted on install.
	 *
	 * @return void
	 */
	private function run_feature_install(): void {
		if ( class_exists( '\WC_Install' ) ) {
			\WC_Install::create_tables();
		}
	}

	/**
	 * Cascade handler for WP user deletion. Detach any linked actor (set
	 * wp_user_id NULL, status inactive). Actor rows are preserved for
	 * historical order attribution lookups.
	 *
	 * @param int $user_id Deleted WordPress user ID.
	 * @return void
	 */
	public function handle_deleted_user( int $user_id ): void {
		$this->actor_repository->detach_wp_user( $user_id );
	}
}
