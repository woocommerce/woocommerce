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
	 * Plugin file of the retired Additional Variation Images extension.
	 */
	private const LEGACY_EXTENSION_PLUGIN_FILE = 'woocommerce-additional-variation-images/woocommerce-additional-variation-images.php';

	/**
	 * Notice ID used when warning merchants that the legacy extension is still active.
	 */
	private const LEGACY_EXTENSION_NOTICE_ID = 'variation_gallery_legacy_extension_active';

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
		add_action( 'admin_init', array( $this, 'sync_legacy_extension_notice' ) );
	}

	/**
	 * Show or remove an admin notice based on whether the retired Additional
	 * Variation Images extension is still active.
	 *
	 * @return void
	 */
	public function sync_legacy_extension_notice(): void {
		if ( ! class_exists( '\WC_Admin_Notices' ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( self::LEGACY_EXTENSION_PLUGIN_FILE ) ) {
			\WC_Admin_Notices::add_custom_notice(
				self::LEGACY_EXTENSION_NOTICE_ID,
				$this->get_legacy_extension_notice_html()
			);
			return;
		}

		\WC_Admin_Notices::remove_notice( self::LEGACY_EXTENSION_NOTICE_ID );
	}

	/**
	 * Build the HTML body for the legacy-extension-active notice. Includes a
	 * one-click deactivation link for users with the `deactivate_plugins`
	 * capability; users without it just see the message. The link points at
	 * WP's built-in plugins.php deactivate handler with a verified nonce, so
	 * we don't need a custom AJAX endpoint or capability check beyond the
	 * one WP already enforces on the receiving end.
	 *
	 * @return string
	 */
	private function get_legacy_extension_notice_html(): string {
		$message_html = wpautop(
			esc_html__( 'Variation galleries are now built into WooCommerce. Your existing images have been migrated, so the Additional Variation Images extension can be deactivated.', 'woocommerce' )
		);

		$action_html = '';
		if ( current_user_can( 'deactivate_plugins' ) ) {
			$deactivate_url = wp_nonce_url(
				admin_url(
					'plugins.php?action=deactivate&plugin=' . rawurlencode( self::LEGACY_EXTENSION_PLUGIN_FILE )
				),
				'deactivate-plugin_' . self::LEGACY_EXTENSION_PLUGIN_FILE
			);

			$action_html = sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				esc_url( $deactivate_url ),
				esc_html__( 'Deactivate Additional Variation Images', 'woocommerce' )
			);
		}

		return sprintf(
			'<h4>%s</h4>%s%s',
			esc_html__( 'Additional Variation Images can be deactivated', 'woocommerce' ),
			$message_html,
			$action_html
		);
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

		// Sentinel set: respect the explicit "no images" choice.
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
