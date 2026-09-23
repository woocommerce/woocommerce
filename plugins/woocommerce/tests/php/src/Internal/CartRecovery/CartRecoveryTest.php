<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CartRecovery;

use Automattic\WooCommerce\Internal\CartRecovery\CartRecovery;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the CartRecovery service.
 */
class CartRecoveryTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

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

	/**
	 * Mailer mock.
	 *
	 * @var \MockPHPMailer
	 */
	private $mailer;

	/**
	 * Write a session row in the stored format: an outer serialized array of individually serialized values.
	 *
	 * @param string $key  Session key.
	 * @param array  $data Recovery data overrides.
	 * @param array  $cart Session cart, or null for one in-stock simple product.
	 * @return array{0: string, 1: array} Key and the cart used.
	 */
	private function write_session( string $key, array $data = array(), ?array $cart = null ): array {
		global $wpdb;

		if ( null === $cart ) {
			$product = WC_Helper_Product::create_simple_product();
			$cart    = array(
				'abc' => array(
					'product_id'   => $product->get_id(),
					'variation_id' => 0,
					'quantity'     => 2,
				),
			);
		}

		$recovery = array_merge(
			array(
				'key'         => $key,
				'email'       => 'shopper@example.com',
				'captured_at' => time() - 7200,
				'last_seen'   => time() - 7200,
				'scheduled'   => true,
			),
			$data
		);

		$wpdb->replace(
			$wpdb->prefix . 'woocommerce_sessions',
			array(
				'session_key'    => $key,
				'session_value'  => maybe_serialize(
					array(
						'cart'                    => maybe_serialize( $cart ),
						'cart_totals'             => maybe_serialize( array( 'total' => '20.00' ) ),
						CartRecovery::SESSION_KEY => maybe_serialize( $recovery ),
					)
				),
				'session_expiry' => time() + DAY_IN_SECONDS,
			)
		);

		return array( $key, $cart );
	}

	/**
	 * Enable the email in the mailer and reset the mail mock.
	 */
	private function prepare_mailer(): void {
		WC()->mailer()->init();
		WC()->mailer()->get_emails()['WC_Email_Customer_Cart_Recovery']->enabled = 'yes';
		$this->mailer = tests_retrieve_phpmailer_instance();
		reset_phpmailer_instance();
		$this->mailer = tests_retrieve_phpmailer_instance();
	}

	/**
	 * @testdox Should send one email with the link and UTM tags for an eligible session.
	 */
	public function test_send_happy_path(): void {
		$this->prepare_mailer();
		list( $key, $cart ) = $this->write_session( 't_happy' );
		$product_id         = $cart['abc']['product_id'];

		$this->sut->handle_send( $key );

		$this->assertCount( 1, $this->mailer->mock_sent );
		$body = $this->mailer->get_sent()->body;
		$this->assertStringContainsString( 'products=' . $product_id . ':2', $body );
		$this->assertStringContainsString( 'utm_campaign=cart_recovery', $body );
	}

	/**
	 * @testdox Should not send twice to the same address within 24 hours.
	 */
	public function test_send_rate_limited_per_address(): void {
		$this->prepare_mailer();
		$this->write_session( 't_one' );
		$this->write_session( 't_two' );

		$this->sut->handle_send( 't_one' );
		$this->sut->handle_send( 't_two' );

		$this->assertCount( 1, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should skip without sending.
	 *
	 * @testWith [{"email": "not-an-email"}, "missing email"]
	 *           [{"key": "t_other"}, "key mismatch"]
	 *           [{"last_seen": 1}, "idle for more than a day"]
	 *
	 * @param array  $data   Recovery data overrides.
	 * @param string $reason Reason, for the failure message.
	 */
	public function test_send_skips( array $data, string $reason ): void {
		$this->prepare_mailer();
		$this->write_session( 't_skip', $data );

		$this->sut->handle_send( 't_skip' );

		$this->assertCount( 0, $this->mailer->mock_sent, "Should skip: {$reason}" );
	}

	/**
	 * @testdox Should skip a session that does not exist.
	 */
	public function test_send_skips_missing_session(): void {
		$this->prepare_mailer();

		$this->sut->handle_send( 't_missing' );

		$this->assertCount( 0, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should reschedule while the shopper is still active, when run through Action Scheduler.
	 */
	public function test_reschedules_when_active_through_runner(): void {
		$this->prepare_mailer();
		add_action( CartRecovery::ACTION_HOOK, array( $this->sut, 'handle_send' ) );
		$this->write_session( 't_active', array( 'last_seen' => time() - 60 ) );
		$action_id = as_schedule_single_action( time() - 1, CartRecovery::ACTION_HOOK, array( 't_active' ), CartRecovery::ACTION_GROUP, true );

		\ActionScheduler::runner()->process_action( $action_id );

		$this->assertCount( 0, $this->mailer->mock_sent );
		$this->assertCount( 1, $this->get_pending( 't_active' ), 'A new pending action must exist after the running one completes' );
	}

	/**
	 * @testdox Should reschedule, not drop, when the cart is below the minimum total.
	 */
	public function test_below_minimum_reschedules(): void {
		$this->prepare_mailer();
		update_option(
			'woocommerce_customer_cart_recovery_settings',
			array(
				'enabled'        => 'yes',
				'delay_minutes'  => '60',
				'min_cart_total' => '50',
			)
		);
		$this->write_session( 't_small' );

		$this->sut->handle_send( 't_small' );

		$this->assertCount( 0, $this->mailer->mock_sent );
		$this->assertCount( 1, $this->get_pending( 't_small' ) );
	}

	/**
	 * @testdox Should skip carts with a product in an excluded parent category.
	 */
	public function test_excluded_parent_category(): void {
		$this->prepare_mailer();
		$parent  = wp_insert_term( 'Gifts', 'product_cat' );
		$child   = wp_insert_term( 'Gift cards', 'product_cat', array( 'parent' => $parent['term_id'] ) );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( array( $child['term_id'] ) );
		$product->save();
		update_option(
			'woocommerce_customer_cart_recovery_settings',
			array(
				'enabled'             => 'yes',
				'delay_minutes'       => '60',
				'excluded_categories' => array( $parent['term_id'] ),
			)
		);
		$this->write_session(
			't_cat',
			array(),
			array(
				'abc' => array(
					'product_id'   => $product->get_id(),
					'variation_id' => 0,
					'quantity'     => 1,
				),
			)
		);

		$this->sut->handle_send( 't_cat' );

		$this->assertCount( 0, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should skip logged-in customers with an excluded role.
	 */
	public function test_excluded_role(): void {
		$this->prepare_mailer();
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		update_option(
			'woocommerce_customer_cart_recovery_settings',
			array(
				'enabled'        => 'yes',
				'delay_minutes'  => '60',
				'excluded_roles' => array( 'shop_manager' ),
			)
		);
		$this->write_session( (string) $user_id );

		$this->sut->handle_send( (string) $user_id );

		$this->assertCount( 0, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should block on a paid order but not on a pending one, under both order storages.
	 *
	 * @testWith [true, "processing", 0]
	 *           [true, "pending", 1]
	 *           [false, "processing", 0]
	 *           [false, "pending", 1]
	 *
	 * @param bool   $hpos     Whether HPOS is authoritative.
	 * @param string $status   Order status.
	 * @param int    $expected Emails sent.
	 */
	public function test_order_since_capture( bool $hpos, string $status, int $expected ): void {
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$original = OrderUtil::custom_orders_table_usage_is_enabled();
		if ( $hpos ) {
			$this->setup_cot();
		} else {
			$this->toggle_cot_feature_and_usage( false );
		}

		try {
			$this->write_session( 't_order' );
			$order = wc_create_order();
			$order->set_billing_email( 'shopper@example.com' );
			$order->set_status( $status );
			$order->save();
			// After the order, so the order status emails are not counted.
			$this->prepare_mailer();

			$this->sut->handle_send( 't_order' );
		} finally {
			if ( $hpos ) {
				$this->clean_up_cot_setup();
			}
			$this->toggle_cot_feature_and_usage( $original );
		}

		$this->assertCount( $expected, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should drop variations with an "any" attribute and skip when nothing is left.
	 */
	public function test_any_attribute_variation_is_dropped(): void {
		$this->prepare_mailer();
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();
		$variation  = wc_get_product( $variations[0] );
		$variation->set_attributes( array( 'pa_size' => '' ) );
		$variation->save();
		$this->write_session(
			't_any',
			array(),
			array(
				'abc' => array(
					'product_id'   => $variable->get_id(),
					'variation_id' => $variation->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->sut->handle_send( 't_any' );

		$this->assertCount( 0, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should honor the eligibility filter only when it returns exactly true.
	 *
	 * @testWith [false, 0]
	 *           ["yes", 0]
	 *           [true, 1]
	 *
	 * @param mixed $filtered Filter return.
	 * @param int   $expected Emails sent.
	 */
	public function test_eligibility_filter( $filtered, int $expected ): void {
		$this->prepare_mailer();
		$this->write_session( 't_filter' );
		add_filter(
			'woocommerce_cart_recovery_is_eligible',
			static function () use ( $filtered ) {
				return $filtered;
			}
		);

		$this->sut->handle_send( 't_filter' );

		$this->assertCount( $expected, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should stop at the daily site limit.
	 */
	public function test_daily_limit(): void {
		$this->prepare_mailer();
		add_filter(
			'woocommerce_cart_recovery_daily_limit',
			static function () {
				return 0;
			}
		);
		$this->write_session( 't_daily' );

		$this->sut->handle_send( 't_daily' );

		$this->assertCount( 0, $this->mailer->mock_sent );
	}

	/**
	 * @testdox Should read a session saved by the real handler, with a warm and a flushed cache.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $flush Whether to flush the object cache before sending.
	 */
	public function test_reads_real_session_round_trip( bool $flush ): void {
		$this->prepare_mailer();
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		$handler = new \WC_Session_Handler();
		$handler->init();
		$product = WC_Helper_Product::create_simple_product();
		$handler->set(
			'cart',
			array(
				'abc' => array(
					'product_id'   => $product->get_id(),
					'variation_id' => 0,
					'quantity'     => 1,
				),
			)
		);
		$handler->set( 'cart_totals', array( 'total' => '10.00' ) );
		$handler->set(
			CartRecovery::SESSION_KEY,
			array(
				'key'         => (string) $user_id,
				'email'       => 'real@example.com',
				'captured_at' => time() - 7200,
				'last_seen'   => time() - 7200,
				'scheduled'   => true,
			)
		);
		$handler->save_data();
		if ( $flush ) {
			wp_cache_flush();
		}

		$this->sut->handle_send( (string) $user_id );

		$this->assertCount( 1, $this->mailer->mock_sent );
		$this->assertSame( 'real@example.com', $this->mailer->get_sent()->to[0][0] );
	}

	/**
	 * @testdox Should rebuild the cart and keep UTM args when the link is followed.
	 */
	public function test_link_rebuilds_cart(): void {
		$this->prepare_mailer();
		list( $key, $cart ) = $this->write_session( 't_link' );
		$this->sut->handle_send( $key );
		preg_match( '/href="([^"]+checkout-link[^"]+)"/', $this->mailer->get_sent()->body, $matches );
		$url = html_entity_decode( $matches[1] );
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$_GET                    = $query;
		$_SERVER['QUERY_STRING'] = (string) wp_parse_url( $url, PHP_URL_QUERY );
		WC()->cart->empty_cart();

		$link   = new \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutLink();
		$method = new \ReflectionMethod( $link, 'get_checkout_link' );
		$method->setAccessible( true );
		$redirect = $method->invoke( $link );

		$this->assertSame( 2, WC()->cart->get_cart_contents_count() );
		$this->assertStringContainsString( 'utm_campaign=cart_recovery', $redirect );
		$this->assertStringNotContainsString( 'products=', $redirect );
	}

	/**
	 * @testdox Should append cart recovery text to the suggested privacy policy.
	 */
	public function test_privacy_policy_text(): void {
		$content = $this->sut->handle_wc_privacy_policy_content( '<div>Base</div>' );

		$this->assertStringStartsWith( '<div>Base</div>', $content );
		$this->assertStringContainsString( 'one email', $content );
	}

	/**
	 * @testdox Should register an eraser that unschedules the user's job.
	 */
	public function test_eraser_unschedules_user_job(): void {
		$user_id = self::factory()->user->create( array( 'user_email' => 'erase@example.com' ) );
		as_schedule_single_action( time() + 3600, CartRecovery::ACTION_HOOK, array( (string) $user_id ), CartRecovery::ACTION_GROUP );

		$erasers = $this->sut->handle_wp_privacy_personal_data_erasers( array() );
		$result  = call_user_func( $erasers['woocommerce-cart-recovery']['callback'], 'erase@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
		$this->assertTrue( $result['done'] );
		$this->assertCount( 0, $this->get_pending( (string) $user_id ) );
	}

	/**
	 * @testdox Should report nothing removed for an address without an account.
	 */
	public function test_eraser_guest_address(): void {
		$result = $this->sut->erase_personal_data( 'guest@example.com', 1 );

		$this->assertFalse( $result['items_removed'] );
		$this->assertTrue( $result['done'] );
	}
}
