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

		$tests['direct']['woocommerce_required_pages'] = array(
			'label' => __( 'WooCommerce required pages are configured', 'woocommerce' ),
			'test'  => array( $this, 'check_required_pages' ),
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
	 * Check whether all required WooCommerce pages are assigned and published.
	 *
	 * Returns a 'critical' result when any required page (shop, cart, checkout,
	 * myaccount) is missing or not in 'publish' status, and 'good' when all
	 * pages are correctly configured.
	 *
	 * @return array WP Site Health result array.
	 */
	public function check_required_pages(): array {
		$required = array(
			'shop'      => __( 'Shop', 'woocommerce' ),
			'cart'      => __( 'Cart', 'woocommerce' ),
			'checkout'  => __( 'Checkout', 'woocommerce' ),
			'myaccount' => __( 'My Account', 'woocommerce' ),
		);
		$missing = array();
		foreach ( $required as $key => $label ) {
			$page_id = (int) get_option( "woocommerce_{$key}_page_id" );
			if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
				$missing[] = $label;
			}
		}

		$result = empty( $missing )
			? array(
				'label'       => __( 'WooCommerce required pages are configured', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
				'description' => '<p>' . esc_html__( 'All required WooCommerce pages are assigned and published.', 'woocommerce' ) . '</p>',
				'actions'     => '',
				'test'        => 'woocommerce_required_pages',
			)
			: array(
				'label'       => __( 'WooCommerce required pages are missing', 'woocommerce' ),
				'status'      => 'critical',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'red' ),
				'description' => '<p>' . esc_html(
					sprintf( __( 'These required WooCommerce pages are missing or unpublished: %s.', 'woocommerce' ), implode( ', ', $missing ) )
				) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced' ) ),
					esc_html__( 'Configure pages', 'woocommerce' )
				),
				'test'        => 'woocommerce_required_pages',
			);

		return $this->apply_result_filters( 'required_pages', $result );
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
