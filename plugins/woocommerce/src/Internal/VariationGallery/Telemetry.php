<?php
/**
 * Telemetry for the variation gallery feature.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracker snapshot + Tracks events for the merged variation gallery feature.
 */
class Telemetry implements RegisterHooksInterface {

	public const EVENT_SAVE_SUCCEEDED      = 'variation_gallery_save_succeeded';
	public const EVENT_SAVE_FAILED         = 'variation_gallery_save_failed';
	public const EVENT_MIGRATION_COMPLETED = 'variation_gallery_migration_completed';

	private const LEGACY_PLUGIN_FILE = 'woocommerce-additional-variation-images/woocommerce-additional-variation-images.php';

	/**
	 * Register the tracker snapshot filter.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_tracker_data', array( $this, 'add_snapshot_to_tracker_data' ), 10, 1 );
	}

	/**
	 * Append the variation gallery snapshot fields to WC_Tracker's payload.
	 *
	 * @param array $data The aggregated tracker data.
	 * @return array
	 */
	public function add_snapshot_to_tracker_data( array $data ): array {
		$data['variation_gallery'] = self::collect_snapshot();
		return $data;
	}

	/**
	 * Collect the variation gallery snapshot fields.
	 *
	 * @return array<string, mixed>
	 */
	public static function collect_snapshot(): array {
		global $wpdb;

		$option_value         = get_option( Package::ENABLE_OPTION_NAME, '' );
		$variant_assignment   = (int) get_option( 'woocommerce_remote_variant_assignment', 0 );
		$cohort               = ( $variant_assignment > 0 && $variant_assignment <= 5 ) ? 'treatment' : 'control';
		$legacy_plugin_active = self::is_legacy_plugin_active();
		$legacy_plugin_file   = WP_PLUGIN_DIR . '/' . self::LEGACY_PLUGIN_FILE;

		$migrated_variation_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
				LegacyVariationGalleryCompatibility::get_core_managed_meta_key()
			)
		);

		$variation_gallery_rows = $wpdb->get_col(
			"SELECT pm.meta_value
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_product_image_gallery'
			   AND pm.meta_value <> ''
			   AND p.post_type = 'product_variation'"
		);

		$authored_variation_count     = 0;
		$total_image_count            = 0;
		$single_image_variation_count = 0;
		$multi_image_variation_count  = 0;

		foreach ( $variation_gallery_rows as $meta_value ) {
			$image_ids = wp_parse_id_list( $meta_value );
			$count     = count( $image_ids );
			if ( 0 === $count ) {
				continue;
			}
			++$authored_variation_count;
			$total_image_count += $count;
			if ( 1 === $count ) {
				++$single_image_variation_count;
			} else {
				++$multi_image_variation_count;
			}
		}

		$authored_without_legacy_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} core_pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = core_pm.post_id
			 LEFT JOIN {$wpdb->postmeta} legacy_pm
			   ON legacy_pm.post_id = core_pm.post_id
			   AND legacy_pm.meta_key = '_wc_additional_variation_images'
			 WHERE core_pm.meta_key = '_product_image_gallery'
			   AND core_pm.meta_value <> ''
			   AND p.post_type = 'product_variation'
			   AND legacy_pm.post_id IS NULL"
		);

		return array(
			'feature_enabled'               => 'yes' === $option_value ? 'yes' : 'no',
			'feature_option_explicit'       => '' === $option_value ? 'no' : 'yes',
			'remote_variant_cohort'         => $cohort,
			'legacy_avi_plugin_active'      => $legacy_plugin_active ? 'yes' : 'no',
			'legacy_avi_plugin_installed'   => file_exists( $legacy_plugin_file ) ? 'yes' : 'no',
			'migrated_variation_count'      => $migrated_variation_count,
			'authored_variation_count'      => $authored_variation_count,
			'authored_without_legacy_count' => $authored_without_legacy_count,
			'total_image_count'             => $total_image_count,
			'single_image_variation_count'  => $single_image_variation_count,
			'multi_image_variation_count'   => $multi_image_variation_count,
		);
	}

	/**
	 * Record a Tracks event.
	 *
	 * @param string               $event_name One of the `EVENT_*` class constants.
	 * @param array<string, mixed> $properties Event properties to attach.
	 * @return void
	 */
	public static function record_event( string $event_name, array $properties = array() ): void {
		if ( ! function_exists( 'wc_admin_record_tracks_event' ) ) {
			return;
		}
		wc_admin_record_tracks_event( $event_name, $properties );
	}

	/**
	 * Whether the legacy Additional Variation Images extension is currently
	 * active.
	 *
	 * @return bool
	 */
	private static function is_legacy_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( self::LEGACY_PLUGIN_FILE );
	}
}
