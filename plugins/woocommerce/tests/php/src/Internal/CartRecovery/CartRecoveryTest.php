<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CartRecovery;

use Automattic\WooCommerce\Internal\CartRecovery\CartRecovery;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the CartRecovery service.
 */
class CartRecoveryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CartRecovery
	 */
	private $sut;

	/**
	 * REMOTE_ADDR before the test, restored in tearDown.
	 *
	 * @var string|null
	 */
	private $remote_addr;

	/**
	 * Enable the flag and the email, and start with an empty cart.
	 */
	public function setUp(): void {
		parent::setUp();

		// Without an IP the per-IP schedule limit is skipped; the limit has its own test.
		$this->remote_addr = $_SERVER['REMOTE_ADDR'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Saved verbatim to restore in tearDown.
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_REAL_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'] );

		update_option( 'woocommerce_feature_cart_recovery_enabled', 'yes' );
		update_option(
			'woocommerce_customer_cart_recovery_settings',
			array(
				'enabled'       => 'yes',
				'delay_minutes' => '60',
			)
		);

		$this->sut = new CartRecovery();
		$this->sut->maybe_register_hooks();

		WC()->cart->empty_cart();
		WC()->session->set( CartRecovery::SESSION_KEY, null );
		as_unschedule_all_actions( CartRecovery::ACTION_HOOK );
	}

	/**
	 * Restore REMOTE_ADDR, which no base class resets.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->remote_addr ) {
				$_SERVER['REMOTE_ADDR'] = $this->remote_addr;
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should schedule only one new session per IP per minute.
	 */
	public function test_ip_limit(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		$this->add_product_to_cart();
		$first_key = (string) WC()->session->get_customer_id();
		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=one%40example.com' );

		$data              = WC()->session->get( CartRecovery::SESSION_KEY );
		$data['key']       = 't_second';
		$data['scheduled'] = false;
		$property          = new \ReflectionProperty( \WC_Session::class, '_customer_id' );
		$property->setAccessible( true );
		$property->setValue( WC()->session, 't_second' );
		WC()->session->set( CartRecovery::SESSION_KEY, $data );
		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=two%40example.com' );

		$this->assertCount( 1, $this->get_pending( $first_key ) );
		$this->assertCount( 0, $this->get_pending( 't_second' ), 'Second session from the same IP within a minute must wait' );
		$this->assertFalse( WC()->session->get( CartRecovery::SESSION_KEY )['scheduled'], 'It retries on the next request' );
	}

	/**
	 * Put one product in the cart.
	 */
	private function add_product_to_cart(): void {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
	}

	/**
	 * Pending recovery actions for a session key.
	 *
	 * @param string $key Session key.
	 * @return array
	 */
	private function get_pending( string $key ): array {
		return as_get_scheduled_actions(
			array(
				'hook'   => CartRecovery::ACTION_HOOK,
				'args'   => array( $key ),
				'group'  => CartRecovery::ACTION_GROUP,
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);
	}

	/**
	 * @testdox Should store the email and schedule one job when a valid email is captured.
	 */
	public function test_capture_stores_and_schedules_once(): void {
		$this->add_product_to_cart();
		$key = (string) WC()->session->get_customer_id();

		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=shopper%40example.com' );
		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=shopper%40example.com' );

		$data = WC()->session->get( CartRecovery::SESSION_KEY );
		$this->assertSame( 'shopper@example.com', $data['email'] );
		$this->assertSame( $key, $data['key'] );
		$this->assertTrue( $data['scheduled'] );
		$this->assertCount( 1, $this->get_pending( $key ), 'Repeated captures must not queue a second job' );
	}

	/**
	 * @testdox Should ignore invalid emails and empty carts.
	 *
	 * @testWith ["billing_email=not-an-email", true]
	 *           ["billing_email=shopper%40example.com", false]
	 *           ["", true]
	 *
	 * @param string $post_data Posted checkout form.
	 * @param bool   $with_cart Whether the cart has an item.
	 */
	public function test_capture_ignores_bad_input( string $post_data, bool $with_cart ): void {
		if ( $with_cart ) {
			$this->add_product_to_cart();
		}

		$this->sut->handle_woocommerce_checkout_update_order_review( $post_data );

		$this->assertNull( WC()->session->get( CartRecovery::SESSION_KEY ) );
	}

	/**
	 * @testdox Should not capture while the email is disabled.
	 */
	public function test_capture_skipped_when_email_disabled(): void {
		update_option( 'woocommerce_customer_cart_recovery_settings', array( 'enabled' => 'no' ) );
		$this->add_product_to_cart();

		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=shopper%40example.com' );

		$this->assertNull( WC()->session->get( CartRecovery::SESSION_KEY ) );
	}

	/**
	 * @testdox Should capture from the Store API customer update.
	 */
	public function test_capture_from_store_api(): void {
		$this->add_product_to_cart();
		WC()->customer->set_billing_email( 'blocks@example.com' );

		$this->sut->handle_woocommerce_store_api_cart_update_customer_from_request( WC()->customer );

		$this->assertSame( 'blocks@example.com', WC()->session->get( CartRecovery::SESSION_KEY )['email'] );
	}

	/**
	 * @testdox Should capture on a failed classic checkout submit only.
	 */
	public function test_capture_after_failed_validation(): void {
		$this->add_product_to_cart();
		$errors = new \WP_Error();

		$this->sut->handle_woocommerce_after_checkout_validation( array( 'billing_email' => 'a@example.com' ), $errors );
		$this->assertNull( WC()->session->get( CartRecovery::SESSION_KEY ), 'No errors means the order goes through' );

		$errors->add( 'validation', 'Missing field' );
		$this->sut->handle_woocommerce_after_checkout_validation( array( 'billing_email' => 'a@example.com' ), $errors );
		$this->assertSame( 'a@example.com', WC()->session->get( CartRecovery::SESSION_KEY )['email'] );
	}

	/**
	 * @testdox Should clear data and unschedule when the cart is emptied.
	 */
	public function test_clear_on_cart_emptied(): void {
		$this->add_product_to_cart();
		$key = (string) WC()->session->get_customer_id();
		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=shopper%40example.com' );

		WC()->cart->empty_cart();

		$this->assertNull( WC()->session->get( CartRecovery::SESSION_KEY ) );
		$this->assertCount( 0, $this->get_pending( $key ) );
	}

	/**
	 * @testdox Should drop data captured under another session key, as after a session clone.
	 */
	public function test_drops_data_from_another_key(): void {
		$this->add_product_to_cart();
		WC()->session->set(
			CartRecovery::SESSION_KEY,
			array(
				'key'         => 't_someoneelse',
				'email'       => 'victim@example.com',
				'captured_at' => time(),
				'last_seen'   => time(),
				'scheduled'   => true,
			)
		);

		$this->sut->handle_woocommerce_cart_loaded_from_session( WC()->cart );

		$this->assertNull( WC()->session->get( CartRecovery::SESSION_KEY ) );
	}

	/**
	 * @testdox Should move the job to the user key when a guest logs in.
	 */
	public function test_guest_to_user_moves_job(): void {
		$this->add_product_to_cart();
		$guest_key = (string) WC()->session->get_customer_id();
		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=shopper%40example.com' );
		$user_id = self::factory()->user->create();

		// migrate_guest_session_to_user_session() switches the key before firing the hook; there is no public setter.
		$property = new \ReflectionProperty( \WC_Session::class, '_customer_id' );
		$property->setAccessible( true );
		$property->setValue( WC()->session, (string) $user_id );
		wp_set_current_user( $user_id );

		$this->sut->handle_woocommerce_guest_session_to_user_id( $guest_key, (string) $user_id );
		$this->sut->handle_woocommerce_cart_loaded_from_session( WC()->cart );

		$this->assertCount( 0, $this->get_pending( $guest_key ), 'Guest job must be removed' );
		$this->assertSame( (string) $user_id, WC()->session->get( CartRecovery::SESSION_KEY )['key'] );
	}

	/**
	 * @testdox Should refresh last_seen only after the throttle interval.
	 */
	public function test_activity_throttle(): void {
		$this->add_product_to_cart();
		$this->sut->handle_woocommerce_checkout_update_order_review( 'billing_email=shopper%40example.com' );
		$data              = WC()->session->get( CartRecovery::SESSION_KEY );
		$data['last_seen'] = time() - 60;
		WC()->session->set( CartRecovery::SESSION_KEY, $data );

		$this->sut->handle_woocommerce_cart_loaded_from_session( WC()->cart );
		$this->assertSame( time() - 60, WC()->session->get( CartRecovery::SESSION_KEY )['last_seen'], 'Inside 5 minutes nothing changes' );

		$data['last_seen'] = time() - 600;
		WC()->session->set( CartRecovery::SESSION_KEY, $data );
		$this->sut->handle_woocommerce_cart_loaded_from_session( WC()->cart );
		$this->assertGreaterThanOrEqual( time() - 1, WC()->session->get( CartRecovery::SESSION_KEY )['last_seen'] );
	}

	/**
	 * @testdox Should clamp the delay setting and validate the filtered value.
	 *
	 * @testWith ["5", null, 900]
	 *           ["99999", null, 82800]
	 *           ["60", -5, 60]
	 *           ["60", "abc", 60]
	 *           ["60", 120, 120]
	 *
	 * @param string $minutes  Stored setting.
	 * @param mixed  $filtered Filter return, or null for no filter.
	 * @param int    $expected Expected seconds.
	 */
	public function test_get_delay_seconds( string $minutes, $filtered, int $expected ): void {
		update_option(
			'woocommerce_customer_cart_recovery_settings',
			array(
				'enabled'       => 'yes',
				'delay_minutes' => $minutes,
			)
		);
		if ( null !== $filtered ) {
			add_filter(
				'woocommerce_cart_recovery_delay_seconds',
				static function () use ( $filtered ) {
					return $filtered;
				}
			);
		}

		$this->assertSame( $expected, $this->sut->get_delay_seconds() );
	}

	/**
	 * @testdox Should unschedule every pending job when the feature is turned off.
	 */
	public function test_flag_off_unschedules(): void {
		as_schedule_single_action( time() + 3600, CartRecovery::ACTION_HOOK, array( 't_abc' ), CartRecovery::ACTION_GROUP );

		$this->sut->handle_feature_enabled_changed( 'cart_recovery', false );

		$this->assertCount( 0, $this->get_pending( 't_abc' ) );
	}
}
