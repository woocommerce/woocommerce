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

	/**
	 * Return the prefixed test slug used by WordPress Site Health.
	 *
	 * @return string The Site Health test id.
	 */
	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	/**
	 * Return the human-readable label for this check.
	 *
	 * @return string The human-readable check title.
	 */
	public function get_label(): string {
		return __( 'WooCommerce product lookup table', 'woocommerce' );
	}

	/**
	 * Whether this check runs asynchronously.
	 *
	 * @return bool Whether this check runs asynchronously.
	 */
	public function is_async(): bool {
		return true;
	}

	/**
	 * Run the check.
	 *
	 * @return array WP Site Health result array.
	 */
	public function run(): array {
		try {
			$lookup   = $this->count_lookup_rows();
			$products = $this->count_published_products();
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		/**
		 * Filters the drift percentage threshold above which the lookup table is considered out of sync.
		 *
		 * @since 11.0.0
		 *
		 * @param int $threshold The drift percentage threshold.
		 */
		$threshold = (int) apply_filters(
			'woocommerce_site_health_check_' . self::ID . '_threshold',
			self::DEFAULT_THRESHOLD
		);

		$drift = $products > 0 ? abs( $lookup - $products ) / max( 1, $products ) * 100 : 0.0;

		if ( ( 0 === $lookup && $products > 0 ) || $drift > $threshold ) {
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce product lookup table is out of sync', 'woocommerce' ),
					'status'      => 'recommended',
					'badge'       => array(
						'label' => __( 'Performance', 'woocommerce' ),
						'color' => 'orange',
					),
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
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'WooCommerce product lookup table is in sync', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'green',
				),
				'description' => '<p>' . esc_html__( 'The product lookup table row count is within healthy bounds of the published product count.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}

	/**
	 * Count the rows in the product meta lookup table.
	 *
	 * @return int The number of rows in the lookup table, or 0 when the table is absent.
	 */
	public function count_lookup_rows(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'wc_product_meta_lookup';
		if ( ! $this->table_exists( $table ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Count the published products and product variations.
	 *
	 * @return int The number of published products and variations.
	 */
	public function count_published_products(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation') AND post_status = 'publish'" );
	}

	/**
	 * Determine whether the given database table exists.
	 *
	 * @param string $table The fully qualified table name.
	 * @return bool Whether the table exists.
	 */
	private function table_exists( string $table ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Apply enable/result filters and finalize.
	 *
	 * @param array $base Base result array (without the 'test' key).
	 * @return array Filtered result, or empty array when the check is disabled.
	 */
	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		/**
		 * Filters whether this Site Health check is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether the check is enabled.
		 */
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		/**
		 * Filters the result array returned by this Site Health check.
		 *
		 * @since 11.0.0
		 *
		 * @param array $base The Site Health result array.
		 */
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	/**
	 * Build an error result when the check itself throws.
	 *
	 * @param \Throwable $e The caught exception.
	 * @return array WP Site Health result array.
	 */
	private function error_result( \Throwable $e ): array {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->error( $e->getMessage(), array( 'source' => 'site-health' ) );
		}
		return $this->finish(
			array(
				'label'       => __( 'WooCommerce could not run a Site Health check', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'gray',
				),
				'description' => '<p>' . esc_html__( 'WooCommerce was unable to run this check. See the site-health log channel for details.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}
}
