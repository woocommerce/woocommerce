<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Checks whether the WooCommerce product lookup table is in sync with published products.
 *
 * @internal
 */
class ProductLookupTableCheck {

	public const ID = 'product_lookup_table';

	private const DEFAULT_THRESHOLD = 5;

	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	public function get_label(): string {
		return __( 'Woo: WooCommerce product lookup table', 'woocommerce' );
	}

	public function is_async(): bool {
		return true;
	}

	public function run(): array {
		try {
			$lookup   = $this->count_lookup_rows();
			$products = $this->count_published_products();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		$threshold = (int) apply_filters(
			'woocommerce_site_health_check_' . self::ID . '_threshold',
			self::DEFAULT_THRESHOLD
		);

		$drift = $products > 0 ? abs( $lookup - $products ) / max( 1, $products ) * 100 : 0.0;

		if ( ( $lookup === 0 && $products > 0 ) || $drift > $threshold ) {
			return $this->finish( array(
				'label'       => __( 'Woo: WooCommerce product lookup table is out of sync', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html(
					sprintf(
						/* translators: 1: lookup row count, 2: published product count, 3: drift percentage */
						__( 'The product lookup table has %1$s rows but there are %2$s published products (%3$s%% drift). An out-of-sync lookup table can slow down product queries.', 'woocommerce' ),
						number_format_i18n( $lookup ),
						number_format_i18n( $products ),
						number_format_i18n( (int) round( $drift ) )
					)
				) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ),
					esc_html__( 'Regenerate product lookup table', 'woocommerce' )
				),
			) );
		}

		return $this->finish( array(
			'label'       => __( 'Woo: WooCommerce product lookup table is in sync', 'woocommerce' ),
			'status'      => 'good',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'green' ),
			'description' => '<p>' . esc_html__( 'The product lookup table row count is within healthy bounds of the published product count.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}

	public function count_lookup_rows(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'wc_product_meta_lookup';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public function count_published_products(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation') AND post_status = 'publish'" );
	}

	private function table_exists( string $table ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	private function error_result( \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish( array(
			'label'       => __( 'Woo: WooCommerce could not run a Site Health check', 'woocommerce' ),
			'status'      => 'recommended',
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'gray' ),
			'description' => '<p>' . esc_html__( 'WooCommerce was unable to run this check. See the site-health log channel for details.', 'woocommerce' ) . '</p>',
			'actions'     => '',
		) );
	}
}
