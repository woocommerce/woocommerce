<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * Detects whether wp_postmeta has an index on meta_value.
 *
 * @internal
 */
class PostmetaIndexCheck {

	public const ID = 'postmeta_meta_value_index';

	/**
	 * Return the prefixed test slug used by WordPress Site Health.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'woocommerce_' . self::ID;
	}

	/**
	 * Return the human-readable label for this check.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'WooCommerce meta_value index', 'woocommerce' );
	}

	/**
	 * Whether this check runs asynchronously.
	 *
	 * @return bool Always false — this is a direct (synchronous) check.
	 */
	public function is_async(): bool {
		return false;
	}

	/**
	 * Run the postmeta index check and return a Site Health result array.
	 *
	 * @return array WP Site Health result array, or empty array when disabled.
	 */
	public function run(): array {
		try {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows    = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta}" );
			$present = false;
			foreach ( (array) $rows as $row ) {
				if ( 'meta_value' === ( $row->Column_name ?? '' ) ) {
					$present = true;
					break;
				}
			}
		} catch ( \Throwable $e ) {
			return $this->error_result( $e );
		}

		if ( $present ) {
			return $this->finish(
				array(
					'label'       => __( 'WooCommerce-related queries can use the postmeta meta_value index', 'woocommerce' ),
					'status'      => 'good',
					'badge'       => array(
						'label' => __( 'Performance', 'woocommerce' ),
						'color' => 'green',
					),
					'description' => '<p>' . esc_html__( 'The postmeta table has an index on meta_value, which speeds up many WooCommerce queries.', 'woocommerce' ) . '</p>',
					'actions'     => '',
				)
			);
		}

		return $this->finish(
			array(
				'label'       => __( 'postmeta.meta_value index is missing', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'orange',
				),
				'description' => '<p>' . esc_html__( 'Adding an index on wp_postmeta.meta_value can substantially speed up WooCommerce price/SKU/stock queries on large stores. A site administrator (or hosting provider) can add this index manually.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}

	/**
	 * Attach the test slug and apply enable/result filters.
	 *
	 * If the `woocommerce_site_health_check_{ID}_enabled` filter returns false,
	 * an empty array is returned so WordPress skips rendering the check entirely.
	 * Otherwise `woocommerce_site_health_check_{ID}_result` is applied so
	 * third-party code can customise the result.
	 *
	 * @param array $base Base result array (without 'test' key).
	 * @return array Filtered result, or empty array when the check is disabled.
	 */
	private function finish( array $base ): array {
		$base['test'] = $this->get_id();
		if ( ! apply_filters( 'woocommerce_site_health_check_' . self::ID . '_enabled', true ) ) {
			return array();
		}
		return (array) apply_filters( 'woocommerce_site_health_check_' . self::ID . '_result', $base );
	}

	/**
	 * Build an error result when the index check itself throws.
	 *
	 * Logs the exception message and delegates to finish() so the enable/result
	 * filters are still applied.
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
				'label'       => __( 'WooCommerce could not run the postmeta index check', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Performance', 'woocommerce' ),
					'color' => 'gray',
				),
				'description' => '<p>' . esc_html__( 'WooCommerce was unable to inspect the postmeta table indexes. Check the site error logs.', 'woocommerce' ) . '</p>',
				'actions'     => '',
			)
		);
	}
}
