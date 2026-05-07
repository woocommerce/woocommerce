<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\Cache\CheckResultCache;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates registration of WooCommerce-specific WordPress Site Health tests.
 *
 * @internal
 */
class SiteHealthChecks {

	/**
	 * The check result cache.
	 *
	 * @var CheckResultCache
	 * Consumed by async check methods added in later tasks.
	 * @phpstan-ignore property.onlyWritten
	 */
	private CheckResultCache $cache;

	/**
	 * Initialize the class instance.
	 *
	 * @param CheckResultCache $cache The check result cache.
	 *
	 * @internal
	 */
	final public function init( CheckResultCache $cache ): void {
		$this->cache = $cache;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'register_tests' ) );
	}

	/**
	 * Add WooCommerce tests to the Site Health test list.
	 *
	 * @param array $tests Existing tests array with 'direct' and 'async' keys.
	 * @return array
	 */
	public function register_tests( array $tests ): array {
		$tests['direct']['woocommerce_pending_db_update'] = array(
			'label' => __( 'WooCommerce database is up to date', 'woocommerce' ),
			'test'  => array( $this, 'check_pending_db_update' ),
		);

		return $tests;
	}

	/**
	 * Check whether a WooCommerce database update is pending.
	 *
	 * Returns a 'critical' result when WC_Install::needs_db_update() is true,
	 * and 'good' when the database is already at the current version.
	 *
	 * @return array WP Site Health result array.
	 */
	public function check_pending_db_update(): array {
		$needs_update = class_exists( '\WC_Install' ) ? \WC_Install::needs_db_update() : false;

		$result = $needs_update
			? array(
				'label'       => __( 'WooCommerce database update required', 'woocommerce' ),
				'status'      => 'critical',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'red' ),
				'description' => '<p>' . esc_html__( 'WooCommerce has pending database updates that should be run to keep the store working correctly.', 'woocommerce' ) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-status' ) ),
					esc_html__( 'Run database update', 'woocommerce' )
				),
				'test'        => 'woocommerce_pending_db_update',
			)
			: array(
				'label'       => __( 'WooCommerce database is up to date', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
				'description' => '<p>' . esc_html__( 'No WooCommerce database updates are pending.', 'woocommerce' ) . '</p>',
				'actions'     => '',
				'test'        => 'woocommerce_pending_db_update',
			);

		return $this->apply_result_filters( 'pending_db_update', $result );
	}

	/**
	 * Apply enable/result filters for a Site Health check.
	 *
	 * If the `woocommerce_site_health_check_{$id}_enabled` filter returns false,
	 * returns an empty array so WordPress skips rendering the check entirely.
	 * Otherwise applies `woocommerce_site_health_check_{$id}_result` to allow
	 * third-party customisation of the result before it reaches WP's UI.
	 *
	 * @param string $id     The check identifier (snake_case, without the 'woocommerce_' prefix).
	 * @param array  $result The default result array to filter.
	 * @return array Filtered result, or empty array when the check is disabled.
	 */
	private function apply_result_filters( string $id, array $result ): array {
		if ( ! apply_filters( "woocommerce_site_health_check_{$id}_enabled", true ) ) {
			return array(); // empty result skips the test in WP's UI.
		}
		/**
		 * Filter a WooCommerce Site Health check result before WP renders it.
		 */
		return (array) apply_filters( "woocommerce_site_health_check_{$id}_result", $result );
	}
}
