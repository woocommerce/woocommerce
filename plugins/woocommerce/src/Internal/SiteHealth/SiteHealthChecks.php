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
			'label' => __( 'Woo: WooCommerce database is up to date', 'woocommerce' ),
			'test'  => array( $this, 'check_pending_db_update' ),
		);

		$tests['direct']['woocommerce_required_pages'] = array(
			'label' => __( 'Woo: WooCommerce required pages are configured', 'woocommerce' ),
			'test'  => array( $this, 'check_required_pages' ),
		);

		$tests['direct']['woocommerce_hpos_status'] = array(
			'label' => __( 'Woo: WooCommerce order storage', 'woocommerce' ),
			'test'  => array( $this, 'check_hpos_status' ),
		);

		$tests['direct']['woocommerce_legacy_rest_api'] = array(
			'label' => __( 'Woo: WooCommerce Legacy REST API', 'woocommerce' ),
			'test'  => array( $this, 'check_legacy_rest_api' ),
		);

		$tests['direct']['woocommerce_https'] = array(
			'label' => __( 'Woo: WooCommerce store uses HTTPS', 'woocommerce' ),
			'test'  => array( $this, 'check_https' ),
		);

		$tests['direct']['woocommerce_payment_gateway'] = array(
			'label' => __( 'Woo: WooCommerce has an active payment gateway', 'woocommerce' ),
			'test'  => array( $this, 'check_payment_gateway' ),
		);

		$postmeta_index = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\PostmetaIndexCheck();
		$tests['direct'][ $postmeta_index->get_id() ] = array(
			'label' => $postmeta_index->get_label(),
			'test'  => array( $postmeta_index, 'run' ),
		);

		$as_stats = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\ActionSchedulerStats();
		$cache    = $this->cache;
		$tests['async']['woocommerce_action_scheduler_overdue'] = array(
			'label'             => __( 'Woo: Action Scheduler backlog', 'woocommerce' ),
			'test'              => 'woocommerce_action_scheduler_overdue',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'action_scheduler_overdue', static fn() => $as_stats->run_overdue() ),
		);
		$tests['async']['woocommerce_action_scheduler_total'] = array(
			'label'             => __( 'Woo: Action Scheduler table size', 'woocommerce' ),
			'test'              => 'woocommerce_action_scheduler_total',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'action_scheduler_total', static fn() => $as_stats->run_total() ),
		);

		$auto = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\AutoloadedOptionsAudit();
		$tests['async']['woocommerce_autoloaded_options'] = array(
			'label'             => __( 'Woo: WooCommerce autoloaded options size', 'woocommerce' ),
			'test'              => 'woocommerce_autoloaded_options',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'autoloaded_options', static fn() => $auto->run() ),
		);

		$sessions = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\SessionsTableCheck();
		$tests['async']['woocommerce_sessions_table'] = array(
			'label'             => __( 'Woo: WooCommerce sessions table size', 'woocommerce' ),
			'test'              => 'woocommerce_sessions_table',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'sessions_table', static fn() => $sessions->run() ),
		);

		$lookup = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\ProductLookupTableCheck();
		$tests['async']['woocommerce_product_lookup_table'] = array(
			'label'             => __( 'Woo: WooCommerce product lookup table', 'woocommerce' ),
			'test'              => 'woocommerce_product_lookup_table',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'product_lookup_table', static fn() => $lookup->run() ),
		);

		$webhooks = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\WebhookFailureCheck();
		$tests['async']['woocommerce_webhook_failures'] = array(
			'label'             => __( 'Woo: WooCommerce webhook deliveries', 'woocommerce' ),
			'test'              => 'woocommerce_webhook_failures',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'webhook_failures', static fn() => $webhooks->run() ),
		);

		$templates = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\TemplateOverrideScanner();
		$tests['async']['woocommerce_outdated_templates'] = array(
			'label'             => __( 'Woo: WooCommerce template overrides', 'woocommerce' ),
			'test'              => 'woocommerce_outdated_templates',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'outdated_templates', static fn() => $templates->run() ),
		);

		$cart_fragments = new \Automattic\WooCommerce\Internal\SiteHealth\Checks\CartFragmentsCheck();
		$tests['async']['woocommerce_cart_fragments_sitewide'] = array(
			'label'             => __( 'Woo: WooCommerce cart fragments load policy', 'woocommerce' ),
			'test'              => 'woocommerce_cart_fragments_sitewide',
			'async'             => true,
			'async_direct_test' => static fn() => $cache->remember( 'cart_fragments_sitewide', static fn() => $cart_fragments->run() ),
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
				'label'       => __( 'Woo: WooCommerce database update required', 'woocommerce' ),
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
				'label'       => __( 'Woo: WooCommerce database is up to date', 'woocommerce' ),
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
				'label'       => __( 'Woo: WooCommerce required pages are configured', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
				'description' => '<p>' . esc_html__( 'All required WooCommerce pages are assigned and published.', 'woocommerce' ) . '</p>',
				'actions'     => '',
				'test'        => 'woocommerce_required_pages',
			)
			: array(
				'label'       => __( 'Woo: WooCommerce required pages are missing', 'woocommerce' ),
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
	 * Check the WooCommerce High-Performance Order Storage (HPOS) configuration.
	 *
	 * Returns 'recommended' when the legacy post-based storage is still active,
	 * or when HPOS is enabled but data sync is still running. Returns 'good'
	 * when HPOS is the sole authoritative storage with sync disabled.
	 *
	 * @return array WP Site Health result array.
	 */
	public function check_hpos_status(): array {
		$hpos_enabled = 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' );
		$sync_enabled = 'yes' === get_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );

		if ( ! $hpos_enabled ) {
			$status = 'recommended';
			$label  = __( 'Woo: WooCommerce is using legacy order storage', 'woocommerce' );
			$desc   = __( 'High-Performance Order Storage (HPOS) provides faster order queries. Consider enabling it.', 'woocommerce' );
		} elseif ( $sync_enabled ) {
			$status = 'recommended';
			$label  = __( 'Woo: HPOS is running with sync enabled', 'woocommerce' );
			$desc   = __( 'Order data is being written to both the legacy and custom tables. Once verified, disable sync to reduce database write overhead.', 'woocommerce' );
		} else {
			$status = 'good';
			$label  = __( 'Woo: WooCommerce order storage is optimized', 'woocommerce' );
			$desc   = __( 'HPOS is enabled and sync is disabled.', 'woocommerce' );
		}

		return $this->apply_result_filters( 'hpos_status', array(
			'label'       => $label,
			'status'      => $status,
			'badge'       => array( 'label' => __( 'Performance', 'woocommerce' ), 'color' => 'orange' ),
			'description' => '<p>' . esc_html( $desc ) . '</p>',
			'actions'     => sprintf(
				'<p><a href="%s">%s</a></p>',
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ) ),
				esc_html__( 'Manage features', 'woocommerce' )
			),
			'test'        => 'woocommerce_hpos_status',
		) );
	}

	/**
	 * Check whether the deprecated WooCommerce Legacy REST API is enabled.
	 *
	 * Returns 'recommended' when the legacy API is enabled (prompting the
	 * merchant to disable it if no integrations require it), and 'good' when
	 * it is disabled.
	 *
	 * @return array WP Site Health result array.
	 */
	public function check_legacy_rest_api(): array {
		$enabled = 'yes' === get_option( 'woocommerce_api_enabled', 'no' );
		$result  = $enabled
			? array(
				'label'       => __( 'Woo: WooCommerce Legacy REST API is enabled', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html__( 'The Legacy REST API is deprecated. If no integrations require it, disable it to reduce surface area.', 'woocommerce' ) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=legacy_api' ) ),
					esc_html__( 'Configure Legacy REST API', 'woocommerce' )
				),
				'test'        => 'woocommerce_legacy_rest_api',
			)
			: array(
				'label'       => __( 'Woo: WooCommerce Legacy REST API is disabled', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'green' ),
				'description' => '<p>' . esc_html__( 'The deprecated Legacy REST API is not enabled.', 'woocommerce' ) . '</p>',
				'actions'     => '',
				'test'        => 'woocommerce_legacy_rest_api',
			);
		return $this->apply_result_filters( 'legacy_rest_api', $result );
	}

	/**
	 * Check whether the store home URL uses HTTPS.
	 *
	 * Returns 'critical' when the home option does not start with 'https://',
	 * and 'good' when it does. HTTPS is required for safe transmission of
	 * payment and account data.
	 *
	 * @return array WP Site Health result array.
	 */
	public function check_https(): array {
		$home_url = (string) get_option( 'home' );
		$is_https = ( 0 === stripos( $home_url, 'https://' ) );
		$result   = $is_https
			? array(
				'label'       => __( 'Woo: Store URL uses HTTPS', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'green' ),
				'description' => '<p>' . esc_html__( 'Your site URL uses HTTPS.', 'woocommerce' ) . '</p>',
				'actions'     => '',
				'test'        => 'woocommerce_https',
			)
			: array(
				'label'       => __( 'Woo: Store URL is not using HTTPS', 'woocommerce' ),
				'status'      => 'critical',
				'badge'       => array( 'label' => __( 'Security', 'woocommerce' ), 'color' => 'red' ),
				'description' => '<p>' . esc_html__( 'Your store must use HTTPS so checkout and account data are protected in transit. Most payment methods will not work without HTTPS enabled on the checkout page.', 'woocommerce' ) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( 'https://woocommerce.com/document/ssl-and-https/' ),
					esc_html__( 'Learn more about HTTPS', 'woocommerce' )
				),
				'test'        => 'woocommerce_https',
			);
		return $this->apply_result_filters( 'https', $result );
	}

	/**
	 * Check whether at least one payment gateway is enabled and available.
	 *
	 * Returns 'recommended' when no available payment gateways are found
	 * (customers cannot complete purchases), and 'good' when one or more
	 * gateways are active.
	 *
	 * @return array WP Site Health result array.
	 */
	public function check_payment_gateway(): array {
		// This is a direct (synchronous) check rather than async because:
		//   1. Having no payment gateway is a critical store-functionality issue
		//      where stale (cached) results would mislead the operator.
		//   2. Gateway initialization is not free, but happens during normal admin
		//      navigation anyway, so the marginal cost on Site Health page load is small.
		$available = WC()->payment_gateways()->get_available_payment_gateways();
		$count     = is_array( $available ) ? count( $available ) : 0;
		$result    = $count > 0
			? array(
				'label'       => __( 'Woo: WooCommerce has an active payment gateway', 'woocommerce' ),
				'status'      => 'good',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'green' ),
				'description' => '<p>' . esc_html(
					sprintf( _n( '%d payment gateway is enabled.', '%d payment gateways are enabled.', $count, 'woocommerce' ), $count )
				) . '</p>',
				'actions'     => '',
				'test'        => 'woocommerce_payment_gateway',
			)
			: array(
				'label'       => __( 'Woo: WooCommerce has no active payment gateway', 'woocommerce' ),
				'status'      => 'recommended',
				'badge'       => array( 'label' => __( 'WooCommerce', 'woocommerce' ), 'color' => 'orange' ),
				'description' => '<p>' . esc_html__( 'Customers cannot complete purchases until at least one payment gateway is enabled.', 'woocommerce' ) . '</p>',
				'actions'     => sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ),
					esc_html__( 'Configure payments', 'woocommerce' )
				),
				'test'        => 'woocommerce_payment_gateway',
			);
		return $this->apply_result_filters( 'payment_gateway', $result );
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
