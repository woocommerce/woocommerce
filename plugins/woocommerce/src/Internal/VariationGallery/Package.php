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
	 * Action Scheduler hook for DB update callbacks.
	 */
	private const UPDATE_CALLBACK_HOOK = 'woocommerce_run_update_callback';

	/**
	 * Action Scheduler group for DB update callbacks.
	 */
	private const UPDATE_CALLBACK_GROUP = 'woocommerce-db-updates';

	/**
	 * The feature id used by `FeaturesController` (Settings → Advanced → Features).
	 */
	public const FEATURE_ID = 'variation_gallery';

	/**
	 * Option backing the variation gallery feature toggle.
	 */
	public const ENABLE_OPTION_NAME = 'wc_feature_woocommerce_additional_variation_images_enabled';

	/**
	 * `woocommerce_remote_variant_assignment` option name.
	 */
	private const REMOTE_VARIANT_OPTION_NAME = 'woocommerce_remote_variant_assignment';

	/**
	 * Highest variant bucket in the canary cohort. Range is 1-120, so
	 * `<= 6` gets exactly 5%. Matches the Brands merge precedent.
	 */
	public const CANARY_MAX_VARIANT = 6;

	/**
	 * Whether the current store is in the canary cohort.
	 *
	 * @internal Removable once the feature is at 100% rollout.
	 * @return bool
	 */
	public static function is_in_canary_cohort(): bool {
		$variant_assignment = (int) get_option( self::REMOTE_VARIANT_OPTION_NAME, 0 );
		return $variant_assignment > 0 && $variant_assignment <= self::CANARY_MAX_VARIANT;
	}

	/**
	 * Whether the merged variation gallery feature is enabled for the current
	 * request.
	 *
	 * Explicit `yes`/`no` on the option wins; unset falls back to the canary
	 * cohort.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$option_value = get_option( self::ENABLE_OPTION_NAME, '' );

		if ( 'yes' === $option_value ) {
			return true;
		}

		if ( 'no' === $option_value ) {
			return false;
		}

		return self::is_in_canary_cohort();
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

		// Action Scheduler initializes on `init`, not `plugins_loaded`.
		add_action( 'init', array( __CLASS__, 'maybe_schedule_migration' ), 20 );
	}

	/**
	 * Schedule the legacy variation gallery migration if it hasn't already
	 * completed and isn't already pending or running.
	 *
	 * @internal
	 */
	public static function maybe_schedule_migration(): void {
		if ( get_option( Migration::COMPLETED_OPTION ) ) {
			return;
		}

		$args = array( 'update_callback' => array( Migration::class, 'run' ) );

		if ( self::has_pending_or_running_migration( $args ) ) {
			return;
		}

		WC()->queue()->add(
			self::UPDATE_CALLBACK_HOOK,
			$args,
			self::UPDATE_CALLBACK_GROUP
		);
	}

	/**
	 * Determine whether the migration is already pending or running.
	 *
	 * @param array<string, array<int, string>> $args Exact callback args for the migration action.
	 * @return bool
	 */
	private static function has_pending_or_running_migration( array $args ): bool {
		if ( null !== WC()->queue()->get_next( self::UPDATE_CALLBACK_HOOK, $args, self::UPDATE_CALLBACK_GROUP ) ) {
			return true;
		}

		$running_actions = WC()->queue()->search(
			array(
				'hook'     => self::UPDATE_CALLBACK_HOOK,
				'args'     => $args,
				'status'   => \ActionScheduler_Store::STATUS_RUNNING,
				'per_page' => 1,
				'group'    => self::UPDATE_CALLBACK_GROUP,
			),
			'ids'
		);

		return ! empty( $running_actions );
	}
}
