<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\FormHandlerService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupRateLimiter;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use WC_Helper_Product;

/**
 * Integration tests for the form-handler + rate-limiter wiring.
 */
class FormHandlerServiceTests extends \WC_Unit_Test_Case {

	/**
	 * @var FormHandlerService
	 */
	private FormHandlerService $sut;

	/**
	 * @var SignupRateLimiter
	 */
	private SignupRateLimiter $rate_limiter;

	/**
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );

		// Start the form handler from the DI container so the wiring matches production.
		$this->sut          = wc_get_container()->get( FormHandlerService::class );
		$this->rate_limiter = wc_get_container()->get( SignupRateLimiter::class );

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_manage_stock( true );
		$this->product->set_stock_quantity( 0 );
		$this->product->set_stock_status( 'outofstock' );
		$this->product->save();

		// Tight thresholds to keep the test concise.
		add_filter( 'woocommerce_bis_signup_rate_limit_max_per_ip', array( $this, 'ip_threshold' ) );
		add_filter( 'woocommerce_bis_signup_rate_limit_max_per_email', array( $this, 'email_threshold' ) );

		// Skip nonce checks by default (matches guest-flow production default).
		add_filter( 'woocommerce_customer_stock_notifications_requires_nonce_check', '__return_false' );

		// Give ourselves a stable simulated client IP.
		$_SERVER['REMOTE_ADDR'] = '203.0.113.77';
	}

	/**
	 * @after
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_bis_signup_rate_limit_max_per_ip', array( $this, 'ip_threshold' ) );
		remove_filter( 'woocommerce_bis_signup_rate_limit_max_per_email', array( $this, 'email_threshold' ) );
		remove_filter( 'woocommerce_customer_stock_notifications_requires_nonce_check', '__return_false' );

		delete_option( 'woocommerce_customer_stock_notifications_allow_signups' );

		$this->clear_transients_by_prefix( SignupRateLimiter::IP_PREFIX );
		$this->clear_transients_by_prefix( SignupRateLimiter::EMAIL_PREFIX );

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );

		$_POST = array();
		unset( $_SERVER['REMOTE_ADDR'] );
		wc_clear_notices();

		parent::tearDown();
	}

	/**
	 * IP threshold for tests.
	 */
	public function ip_threshold(): int {
		return 3;
	}

	/**
	 * Email threshold for tests.
	 */
	public function email_threshold(): int {
		return 2;
	}

	/**
	 * @testdox Sign-ups under the limit are recorded without raising a rate-limit notice.
	 */
	public function test_signup_under_limit_succeeds(): void {
		$this->simulate_signup_post( 'under@example.com' );
		$this->sut->handle_signup();

		$notifications = NotificationQuery::get_notifications(
			array(
				'product_id' => $this->product->get_id(),
				'return'     => 'ids',
			)
		);

		$this->assertCount( 1, $notifications );
		$this->assertFalse( $this->has_rate_limit_notice() );
	}

	/**
	 * @testdox Once the per-email threshold is reached the form handler rejects further sign-ups.
	 */
	public function test_signup_at_email_limit_is_rejected(): void {
		$email = 'serial@example.com';

		// Pre-fill the email counter to its threshold using distinct IPs.
		$this->rate_limiter->record_attempt( '203.0.113.1', $email );
		$this->rate_limiter->record_attempt( '203.0.113.2', $email );

		$this->simulate_signup_post( $email );
		$this->sut->handle_signup();

		$notifications = NotificationQuery::get_notifications(
			array(
				'product_id' => $this->product->get_id(),
				'return'     => 'ids',
			)
		);

		$this->assertCount( 0, $notifications, 'No notification should be created when rate-limited.' );
		$this->assertTrue( $this->has_rate_limit_notice() );
	}

	/**
	 * @testdox Once the per-IP threshold is reached the form handler rejects even new emails.
	 */
	public function test_signup_at_ip_limit_is_rejected(): void {
		$ip = '203.0.113.77';

		// Pre-fill the IP counter to its threshold using distinct emails.
		$this->rate_limiter->record_attempt( $ip, 'a@example.com' );
		$this->rate_limiter->record_attempt( $ip, 'b@example.com' );
		$this->rate_limiter->record_attempt( $ip, 'c@example.com' );

		$this->simulate_signup_post( 'fresh@example.com' );
		$this->sut->handle_signup();

		$notifications = NotificationQuery::get_notifications(
			array(
				'product_id' => $this->product->get_id(),
				'return'     => 'ids',
			)
		);

		$this->assertCount( 0, $notifications, 'No notification should be created when the IP is rate-limited.' );
		$this->assertTrue( $this->has_rate_limit_notice() );
	}

	/**
	 * Populate $_POST with a valid sign-up payload.
	 *
	 * @param string $email Customer email.
	 */
	private function simulate_signup_post( string $email ): void {
		$_POST                      = array();
		$_POST['wc_bis_register']   = '1';
		$_POST['wc_bis_product_id'] = (string) $this->product->get_id();
		$_POST['wc_bis_email']      = $email;
	}

	/**
	 * Whether an error notice matching the rate-limit copy is present.
	 */
	private function has_rate_limit_notice(): bool {
		$notices = wc_get_notices( 'error' );
		foreach ( $notices as $notice ) {
			$text = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : (string) $notice;
			if ( false !== strpos( $text, 'signing up too fast' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Delete all transients with a given prefix.
	 *
	 * @param string $prefix Transient prefix.
	 */
	private function clear_transients_by_prefix( string $prefix ): void {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$like = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		wp_cache_flush();
	}
}
