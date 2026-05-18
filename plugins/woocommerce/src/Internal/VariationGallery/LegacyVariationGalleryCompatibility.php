<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Provides read compatibility for variation galleries stored by the
 * Additional Variation Images extension.
 */
class LegacyVariationGalleryCompatibility implements RegisterHooksInterface {

	/**
	 * Legacy meta key used by the retired extension.
	 */
	private const LEGACY_META_KEY = '_wc_additional_variation_images';

	/**
	 * Marks a variation as explicitly managed by core, so legacy fallback stops applying.
	 */
	private const LEGACY_FALLBACK_DISABLED_META_KEY = '_wc_variation_gallery_legacy_fallback_disabled';

	/**
	 * Get the internal meta key used to mark legacy fallback as disabled.
	 *
	 * @return string
	 */
	public static function get_core_managed_meta_key(): string {
		return self::LEGACY_FALLBACK_DISABLED_META_KEY;
	}

	/**
	 * Mark a variation as managed by core so legacy fallback stops applying.
	 *
	 * @param WC_Product_Variation $variation Variation managed by core.
	 * @return void
	 */
	public static function mark_core_managed( WC_Product_Variation $variation ): void {
		if ( ! metadata_exists( 'post', $variation->get_id(), self::LEGACY_META_KEY ) ) {
			return;
		}

		$variation->update_meta_data( self::LEGACY_FALLBACK_DISABLED_META_KEY, 'yes' );
	}

	/**
	 * Mark a variation ID as managed by core so legacy fallback stops applying.
	 *
	 * @param int $variation_id Variation ID managed by core.
	 * @return void
	 */
	public static function mark_variation_id_core_managed( int $variation_id ): void {
		if ( ! metadata_exists( 'post', $variation_id, self::LEGACY_META_KEY ) ) {
			return;
		}

		update_post_meta( $variation_id, self::LEGACY_FALLBACK_DISABLED_META_KEY, 'yes' );
	}

	/**
	 * Determine whether a variation ID is already managed by core.
	 *
	 * @param int $variation_id Variation ID.
	 * @return bool
	 */
	public static function is_variation_id_core_managed( int $variation_id ): bool {
		return metadata_exists( 'post', $variation_id, self::LEGACY_FALLBACK_DISABLED_META_KEY );
	}

	/**
	 * Register compatibility hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_product_variation_get_gallery_image_ids', array( $this, 'maybe_read_legacy_gallery_image_ids' ), 10, 2 );
	}

	/**
	 * Use legacy variation gallery meta when the core gallery is empty and the
	 * variation has not been marked as core-managed.
	 *
	 * @param array<mixed>         $gallery_image_ids Gallery image IDs already resolved by core.
	 * @param WC_Product_Variation $variation Variation instance.
	 * @return array<int>
	 */
	public function maybe_read_legacy_gallery_image_ids( $gallery_image_ids, WC_Product_Variation $variation ): array {
		// Core has variation images, just normalize.
		if ( ! empty( $gallery_image_ids ) ) {
			return array_values( wp_parse_id_list( $gallery_image_ids ) );
		}

		// Sentinel set: respect the explicit "no images" choice; legacy meta remains for BC.
		if ( self::is_variation_id_core_managed( $variation->get_id() ) ) {
			return array();
		}

		$legacy_gallery_image_ids = get_post_meta( $variation->get_id(), self::LEGACY_META_KEY, true );

		// Nothing to fall back to.
		if ( empty( $legacy_gallery_image_ids ) ) {
			return array();
		}

		// Pre-migration variation: fall back to the legacy extension's meta.
		return array_values( wp_parse_id_list( $legacy_gallery_image_ids ) );
	}
}
