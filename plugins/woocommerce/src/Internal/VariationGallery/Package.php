<?php
/**
 * Package class file for the variation gallery feature.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

defined( 'ABSPATH' ) || exit;

/**
 * Variation gallery package entry point.
 *
 * Registered in `\Automattic\WooCommerce\Packages::$merged_packages` against
 * the `woocommerce-additional-variation-images` slug, so this class is the
 * single bootstrap surface for the merged variation gallery feature.
 */
class Package {

	/**
	 * The feature id used by `FeaturesController` (Settings → Advanced → Features).
	 */
	public const FEATURE_ID = 'variation_gallery';

	public const ENABLE_OPTION_NAME = 'wc_feature_woocommerce_additional_variation_images_enabled';

	/**
	 * Whether the merged variation gallery feature is enabled for the current
	 * request.
	 *
	 * Reads the same option as the Features toggles, so the `FeaturesController`
	 * and the merged-package machinery share a single source of truth. Defaults
	 * to off for the 10.9 canary period.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 'yes' === get_option( self::ENABLE_OPTION_NAME, 'no' );
	}

	/**
	 * Early bootstrap hook fired by `Packages::prepare_packages` at
	 * plugins_loaded priority -100. No-op for the variation gallery feature.
	 *
	 * @internal
	 */
	public static function prepare(): void {
	}

	/**
	 * Initialize the merged variation gallery feature.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$container = wc_get_container();
		$container->get( ClassicVariationGalleryAdmin::class )->register();
		$container->get( LegacyVariationGalleryCompatibility::class )->register();

		// Defer the schedule check until Action Scheduler's data store is
		// initialized, which doesn't happen until WP's `init` hook.
		add_action( 'init', array( __CLASS__, 'maybe_schedule_migration' ), 20 );
	}

	/**
	 * Schedule the legacy variation gallery migration if it hasn't already
	 * completed and isn't already queued.
	 *
	 * Safe to call on every request: short-circuits when the completion
	 * option is set or a pending action already exists for the callback.
	 *
	 * @internal
	 */
	public static function maybe_schedule_migration(): void {
		if ( get_option( Migration::COMPLETED_OPTION ) ) {
			return;
		}

		$update_callback = array( Migration::class, 'run' );

		$pending = WC()->queue()->search(
			array(
				'hook'     => 'woocommerce_run_update_callback',
				'status'   => 'pending',
				'per_page' => 1,
				'group'    => 'woocommerce-db-updates',
			)
		);

		foreach ( $pending as $action ) {
			$args = $action->get_args();
			if ( isset( $args['update_callback'] ) && $args['update_callback'] === $update_callback ) {
				return;
			}
		}

		WC()->queue()->add(
			'woocommerce_run_update_callback',
			array( 'update_callback' => $update_callback ),
			'woocommerce-db-updates'
		);
	}
}
