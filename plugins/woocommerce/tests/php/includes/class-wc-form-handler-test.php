<?php
/**
 * Tests for WC_Form_Handler.
 *
 * @package WooCommerce\Tests\FormHandler
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * WC_Form_Handler tests.
 */
class WC_Form_Handler_Test extends WC_Unit_Test_Case {

	/**
	 * Original GET data.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_get = array();

	/**
	 * Original request URI.
	 *
	 * @var string|null
	 */
	private ?string $original_request_uri = null;

	/**
	 * Original POST data.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_post = array();

	/**
	 * Original REQUEST data.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_request = array();

	/**
	 * Original WooCommerce session.
	 *
	 * @var WC_Session|null
	 */
	private $original_session;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null;

		$this->original_get     = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_post    = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_session = WC()->session;

		if ( ! WC()->session ) {
			WC()->initialize_session();
		}

		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
		wc_clear_notices();
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
		$this->reset_cancel_order_handled_flag();

		$_GET = $this->original_get;
		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}
		$_POST    = $this->original_post;
		$_REQUEST = $this->original_request;

		wp_set_current_user( 0 );
		wc_clear_notices();
		WC()->session = $this->original_session;

		parent::tearDown();
	}

	/**
	 * Intercepts redirects so the tested handler's trailing exit does not run.
	 *
	 * @param string $location Redirect target.
	 * @return never
	 * @throws RuntimeException Always.
	 */
	public function intercept_redirect( string $location ): void {
		throw new RuntimeException( esc_url_raw( $location ) );
	}

	/**
	 * @testdox My Account registration honors its own merchant setting even when checkout registration is enabled.
	 *
	 * @testWith ["no", false]
	 *           ["yes", true]
	 *
	 * @param string $registration_enabled Whether My Account registration is enabled.
	 * @param bool   $expected_customer Whether a customer should be created.
	 */
	public function test_process_registration_respects_myaccount_setting( string $registration_enabled, bool $expected_customer ): void {
		wp_set_current_user( 0 );
		update_option( 'woocommerce_enable_myaccount_registration', $registration_enabled );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
		add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );
		add_filter( 'pre_wp_mail', '__return_true' );

		$_POST             = array(
			'register'                   => 'Register',
			'email'                      => 'registration-policy@example.com',
			'woocommerce-register-nonce' => wp_create_nonce( 'woocommerce-register' ),
		);
		$created_customers = did_action( 'woocommerce_created_customer' );

		WC_Form_Handler::process_registration();

		$this->assertSame( $expected_customer, get_user_by( 'email', 'registration-policy@example.com' ) instanceof WP_User, 'The stored customer must reflect the My Account registration setting.' );
		$this->assertSame( $created_customers + ( $expected_customer ? 1 : 0 ), did_action( 'woocommerce_created_customer' ), 'Disabled registration must not notify customer-creation consumers.' );
		$this->assertSame( $expected_customer ? 0 : 1, wc_notice_count( 'error' ), 'Only disabled registration should produce an error.' );
		if ( ! $expected_customer ) {
			$this->assertTrue( wc_has_notice( 'Registration is disabled.', 'error' ), 'The registration policy error should be shown.' );
		}
	}

	/**
	 * Order statuses when the pay form is posted, and whether pay_action() should take each one on.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public function provider_pay_action_order_statuses(): array {
		return array(
			'pending order is processed'      => array( OrderStatus::PENDING, true ),
			'checkout-draft order is refused' => array( OrderStatus::CHECKOUT_DRAFT, false ),
		);
	}

	/**
	 * @testdox pay_action() takes on a pending order but not a checkout-draft, which has not passed checkout validation.
	 *
	 * @dataProvider provider_pay_action_order_statuses
	 * @covers WC_Form_Handler::pay_action()
	 *
	 * @param string $status           Order status when the form is posted.
	 * @param bool   $expect_processed Whether pay_action() should start processing the order.
	 */
	public function test_pay_action_processes_payable_orders_but_not_checkout_drafts( string $status, bool $expect_processed ): void {
		global $wp;

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( $status );
		$order->save();

		$this->assertTrue( $order->needs_payment(), 'Baseline: needs_payment() is true for both, so only the draft check can tell them apart.' );

		// No payment method is posted, so a processed order stops at "Invalid payment method" instead of redirecting.
		$_GET['key']                       = $order->get_order_key();
		$_POST['woocommerce_pay']          = '1';
		$_REQUEST['woocommerce-pay-nonce'] = wp_create_nonce( 'woocommerce-pay' );
		$wp->query_vars['order-pay']       = $order->get_id();

		$processed = false;
		add_action(
			'woocommerce_before_pay_action',
			function () use ( &$processed ) {
				$processed = true;
			}
		);

		$customer_billing = array(
			'billing_country'  => WC()->customer->get_billing_country(),
			'billing_state'    => WC()->customer->get_billing_state(),
			'billing_postcode' => WC()->customer->get_billing_postcode(),
			'billing_city'     => WC()->customer->get_billing_city(),
		);
		$ob_level         = ob_get_level();

		try {
			WC_Form_Handler::pay_action();
		} finally {
			// pay_action() copies the order's billing location (country, state, postcode, city) onto the shared customer and leaves its output buffer open.
			WC()->customer->set_props( $customer_billing );
			WC()->customer->save();
			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}
			unset( $wp->query_vars['order-pay'], $_GET['key'], $_POST['woocommerce_pay'], $_REQUEST['woocommerce-pay-nonce'] );
		}

		$this->assertSame( $expect_processed, $processed, 'pay_action() must take on a pending order and leave a draft alone.' );
	}

	/**
	 * @testdox cancel_order() redirects to a clean endpoint when no custom redirect is provided.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 */
	public function test_cancel_order_redirects_to_clean_endpoint_without_custom_redirect(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		$this->prepare_cancel_order_request( $order );
		$this->dispatch_cancel_order_expecting_redirect( wp_make_link_relative( $order->get_cancel_endpoint() ) );

		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The order should be cancelled before the clean redirect.' );
	}

	/**
	 * @testdox cancel_order() preserves a filtered cancel URL base when it removes request arguments.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 */
	public function test_cancel_order_preserves_filtered_cancel_url_base(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order             = WC_Helper_Order::create_order( $user_id );
		$filtered_endpoint = home_url( '/filtered-cancel-page/' );
		$filter            = static function ( string $url ) use ( $filtered_endpoint ): string {
			$query = wp_parse_url( $url, PHP_URL_QUERY );
			return $filtered_endpoint . '?' . $query;
		};

		add_filter( 'woocommerce_get_cancel_order_url_raw', $filter );
		$this->prepare_cancel_order_request( $order );
		remove_filter( 'woocommerce_get_cancel_order_url_raw', $filter );

		$this->dispatch_cancel_order_expecting_redirect( wp_make_link_relative( $filtered_endpoint ) );

		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The order should be cancelled before the filtered redirect.' );
	}

	/**
	 * @testdox cancel_order() preserves a custom redirect.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 */
	public function test_cancel_order_preserves_custom_redirect(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order       = WC_Helper_Order::create_order( $user_id );
		$redirect_to = wc_get_page_permalink( 'myaccount' );

		$this->prepare_cancel_order_request( $order, $redirect_to );
		$this->dispatch_cancel_order_expecting_redirect( $redirect_to );

		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The order should be cancelled before the custom redirect.' );
	}

	/**
	 * @testdox cancel_order() returns to direct callers outside wp_loaded and does not arm the deferred redirect.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 * @covers WC_Form_Handler::redirect_after_cancel_order()
	 */
	public function test_cancel_order_returns_to_direct_callers_without_custom_redirect(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		$this->prepare_cancel_order_request( $order );
		WC_Form_Handler::cancel_order();

		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The direct call should cancel the order and return control to the caller.' );

		WC_Form_Handler::redirect_after_cancel_order();

		$this->assertSame( 1, wc_notice_count( 'notice' ), 'The cancellation notice should remain after an unarmed redirect callback.' );
	}

	/**
	 * @testdox cancel_order() returns to direct callers inside a wp_loaded callback, and the redirect completes afterwards.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 * @covers WC_Form_Handler::redirect_after_cancel_order()
	 */
	public function test_cancel_order_returns_to_direct_callers_inside_wp_loaded(): void {
		global $wp_current_filter;

		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		$this->prepare_cancel_order_request( $order );

		$current_filter_backup = $wp_current_filter;
		$wp_current_filter[]   = 'wp_loaded'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate an extension calling the handler from its own wp_loaded callback.

		try {
			WC_Form_Handler::cancel_order();
			$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The nested direct call should cancel the order and return control to the caller.' );

			$this->dispatch_redirect_after_cancel_order_expecting_redirect( wp_make_link_relative( $order->get_cancel_endpoint() ) );
		} finally {
			$wp_current_filter = $current_filter_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action stack after the simulated dispatch.
		}
	}

	/**
	 * @testdox cancel_order() does not handle the same request twice when an earlier wp_loaded callback already called it.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 * @covers WC_Form_Handler::redirect_after_cancel_order()
	 */
	public function test_cancel_order_ignores_repeated_call_for_the_same_request(): void {
		global $wp_current_filter;

		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		$this->prepare_cancel_order_request( $order );

		$current_filter_backup = $wp_current_filter;
		$wp_current_filter[]   = 'wp_loaded'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate an extension calling the handler before the registered priority 20 dispatch.

		try {
			WC_Form_Handler::cancel_order();
			$this->dispatch_cancel_order_expecting_redirect( wp_make_link_relative( $order->get_cancel_endpoint() ) );
		} finally {
			$wp_current_filter = $current_filter_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action stack after the simulated dispatch.
		}

		$this->assertSame( 1, wc_notice_count( 'notice' ), 'The cancellation notice should be added once.' );
		$this->assertSame( 0, wc_notice_count( 'error' ), 'The repeated dispatch must not add a "can no longer be cancelled" error.' );
	}

	/**
	 * @testdox cancel_order() handles each order once per request, in any call order.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 */
	public function test_cancel_order_handles_each_order_once_per_request(): void {
		global $wp_current_filter;

		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$first_order  = WC_Helper_Order::create_order( $user_id );
		$second_order = WC_Helper_Order::create_order( $user_id );

		$current_filter_backup = $wp_current_filter;
		$wp_current_filter[]   = 'wp_loaded'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate the handler running inside wp_loaded.

		try {
			$this->prepare_cancel_order_request( $first_order );
			WC_Form_Handler::cancel_order();
			WC_Form_Handler::cancel_order();

			$this->prepare_cancel_order_request( $second_order );
			WC_Form_Handler::cancel_order();

			$this->prepare_cancel_order_request( $first_order );
			WC_Form_Handler::cancel_order();
		} finally {
			$wp_current_filter = $current_filter_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action stack after the simulated dispatch.
		}

		$this->assertTrue( wc_get_order( $first_order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The first order should be cancelled.' );
		$this->assertTrue( wc_get_order( $second_order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The second order should be cancelled even though the previous request was deduplicated.' );
		$this->assertSame( 2, wc_notice_count( 'notice' ), 'Each order should add exactly one cancellation notice.' );
		$this->assertSame( 0, wc_notice_count( 'error' ), 'Neither the immediate repeat nor the later repeat for the first order should add an error notice.' );
	}

	/**
	 * @testdox redirect_after_cancel_order() still redirects when an extension re-adds cancel_order() at priority 20 during a real wp_loaded dispatch.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 * @covers WC_Form_Handler::redirect_after_cancel_order()
	 */
	public function test_redirect_after_cancel_order_runs_after_a_re_added_handler(): void {
		global $wp_current_filter;

		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		remove_action( 'wp_loaded', 'WC_Form_Handler::cancel_order', 20 );
		add_action( 'wp_loaded', 'WC_Form_Handler::cancel_order', 20 );

		$this->prepare_cancel_order_request( $order );

		$current_filter_backup = $wp_current_filter;

		try {
			do_action( 'wp_loaded' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( wp_make_link_relative( $order->get_cancel_endpoint() ), $e->getMessage(), 'The deferred redirect should still run after the re-added handler.' );
			$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::CANCELLED ), 'The re-added handler should cancel the order before the redirect.' );
			return;
		} finally {
			$wp_current_filter = $current_filter_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The intercepted redirect unwinds do_action() before it pops the action stack.
		}

		$this->fail( 'Expected the wp_loaded dispatch to redirect after handling the cancellation request.' );
	}

	/**
	 * @testdox redirect_after_cancel_order() does nothing when cancel_order() did not handle the request.
	 *
	 * @covers WC_Form_Handler::redirect_after_cancel_order()
	 */
	public function test_redirect_after_cancel_order_does_nothing_when_handler_did_not_run(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		// An extension can remove cancel_order() from wp_loaded, so only the redirect callback runs.
		$this->prepare_cancel_order_request( $order );
		WC_Form_Handler::redirect_after_cancel_order();

		$this->assertTrue( wc_get_order( $order->get_id() )->has_status( OrderStatus::PENDING ), 'The order should stay untouched when only the redirect callback runs.' );
	}

	/**
	 * @testdox cancel_order() redirects to the cart when the request URI is unavailable.
	 * @dataProvider unavailable_request_uri_provider
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 *
	 * @param string|null $request_uri Request URI to use, or null to remove it.
	 */
	public function test_cancel_order_redirects_to_cart_when_request_uri_is_unavailable( ?string $request_uri ): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order = WC_Helper_Order::create_order( $user_id );

		$this->prepare_cancel_order_request( $order );
		if ( null === $request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $request_uri;
		}

		$this->dispatch_cancel_order_expecting_redirect( wc_get_cart_url() );
	}

	/**
	 * Provides unavailable request URI values.
	 *
	 * @return array<string,array{string|null}>
	 */
	public function unavailable_request_uri_provider(): array {
		return array(
			'missing request URI' => array( null ),
			'empty request URI'   => array( '' ),
		);
	}

	/**
	 * @testdox cancel_order() persists the cancellation notice for a fresh guest session.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 */
	public function test_cancel_order_persists_notice_for_fresh_guest_session(): void {
		wp_set_current_user( 0 );
		WC()->session = new WC_Session_Handler();
		WC()->session->init_session_cookie();

		$this->assertFalse( WC()->session->has_session(), 'The guest should start without a cookie-backed session.' );

		$order = WC_Helper_Order::create_order( 0 );
		$this->prepare_cancel_order_request( $order );
		$this->dispatch_cancel_order_expecting_redirect( wp_make_link_relative( $order->get_cancel_endpoint() ) );

		$this->assertTrue( WC()->session->has_session(), 'Cancelling the guest order should establish a session.' );

		WC()->session->save_data();
		$saved_session_data = WC()->session->get_session( WC()->session->get_customer_id(), array() );
		WC()->session->destroy_session();
		$saved_notices = maybe_unserialize( $saved_session_data['wc_notices'] ?? array() );

		$this->assertNotEmpty( $saved_notices['notice'] ?? array(), 'The cancellation notice should be saved for the redirect.' );
	}

	/**
	 * @testdox cancel_order() redirects safely when the order ID belongs to a refund.
	 *
	 * @covers WC_Form_Handler::cancel_order()
	 */
	public function test_cancel_order_redirects_safely_for_refund_id(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$order  = WC_Helper_Order::create_order( $user_id );
		$refund = wc_create_refund(
			array(
				'amount'   => 1,
				'order_id' => $order->get_id(),
				'reason'   => 'Test refund',
			)
		);

		$this->assertInstanceOf( WC_Order_Refund::class, $refund, 'The test requires a refund order.' );
		$this->prepare_cancel_order_request( $order );
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$_GET['order_id']       = (string) $refund->get_id();
		$_SERVER['REQUEST_URI'] = add_query_arg( 'order_id', $refund->get_id(), $request_uri );

		$this->dispatch_cancel_order_expecting_redirect( wp_make_link_relative( $order->get_cancel_endpoint() ) );
	}

	/**
	 * @testdox save_address() persists a valid $address_type address through the selected endpoint.
	 *
	 * @dataProvider provide_address_types
	 * @covers WC_Form_Handler::save_address()
	 *
	 * @param string               $address_type Address type.
	 * @param array<string,string> $fields       Unprefixed address fields.
	 */
	public function test_save_address_persists_valid_address( string $address_type, array $fields ): void {
		global $wp;

		$user_id = self::factory()->user->create(
			array(
				'user_login' => $address_type . '-address-customer',
				'user_email' => $address_type . '-address-customer@example.test',
				'role'       => 'customer',
			)
		);

		wp_set_current_user( $user_id );
		$wp->query_vars['edit-address'] = $address_type;
		$this->prepare_address_request( $address_type, $fields );

		$this->dispatch_address_save_expecting_redirect();

		$customer        = new WC_Customer( $user_id );
		$error_notices   = wc_get_notices( 'error' );
		$success_notices = wc_get_notices( 'success' );

		$this->assertEmpty( $error_notices, 'A valid address should not add error notices.' );
		$this->assertCount( 1, $success_notices, 'A valid address should add one success notice.' );
		$this->assertSame( 'Address changed successfully.', $success_notices[0]['notice'] );

		foreach ( $fields as $field => $expected ) {
			$getter = "get_{$address_type}_{$field}";
			$this->assertSame( $expected, $customer->{$getter}(), "The persisted {$address_type}_{$field} value should match the submitted address." );
		}
	}

	/**
	 * Provides complete billing and shipping address requests.
	 *
	 * @return array<string,array{string,array<string,string>}>
	 */
	public static function provide_address_types(): array {
		return array(
			'billing'  => array(
				'billing',
				array(
					'first_name' => 'Ada',
					'last_name'  => 'Billing',
					'company'    => 'Analytical Engines',
					'address_1'  => '123 Market Street',
					'address_2'  => 'Suite 4',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94105',
					'country'    => 'US',
					'phone'      => '4155550100',
					'email'      => 'ada.billing@example.test',
				),
			),
			'shipping' => array(
				'shipping',
				array(
					'first_name' => 'Grace',
					'last_name'  => 'Shipping',
					'company'    => 'Compiler Works',
					'address_1'  => '456 Madison Avenue',
					'address_2'  => 'Floor 5',
					'city'       => 'New York',
					'state'      => 'NY',
					'postcode'   => '10010',
					'country'    => 'US',
					'phone'      => '2125550100',
				),
			),
		);
	}

	/**
	 * @testdox save_account_details() saves other account fields when an email-like display name is unchanged.
	 *
	 * @covers WC_Form_Handler::save_account_details()
	 */
	public function test_save_account_details_allows_unchanged_email_like_display_name(): void {
		$user_email = 'display-name-customer@example.test';
		$user_id    = self::factory()->user->create(
			array(
				'user_login'   => $user_email,
				'user_email'   => $user_email,
				'display_name' => $user_email,
				'role'         => 'customer',
			)
		);

		wp_set_current_user( $user_id );
		$this->prepare_account_details_request(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => $user_email,
				'account_email'        => $user_email,
			)
		);

		$this->dispatch_account_details_save_expecting_redirect();

		$updated_user = get_userdata( $user_id );

		$this->assertEmpty( wc_get_notices( 'error' ), 'An unchanged email-like display name should not add error notices.' );
		$this->assertSame( 'Jane', $updated_user->first_name, 'First name should be saved when the email-like display name is unchanged.' );
		$this->assertSame( 'Doe', $updated_user->last_name, 'Last name should be saved when the email-like display name is unchanged.' );
		$this->assertSame( 'Jane Doe', $updated_user->display_name, 'Existing customer sync should continue normalizing email-like display names after the save succeeds.' );
	}

	/**
	 * @testdox save_account_details() compares cleaned display names for records that bypassed WordPress sanitization.
	 *
	 * @covers WC_Form_Handler::save_account_details()
	 */
	public function test_save_account_details_allows_unchanged_email_like_display_name_after_cleaning(): void {
		global $wpdb;

		$user_email = 'legacy-display-name@example.test';
		$user_id    = self::factory()->user->create(
			array(
				'user_login'   => 'legacy-display-name-customer',
				'user_email'   => $user_email,
				'display_name' => 'Display Customer',
				'role'         => 'customer',
			)
		);

		// Simulate a legacy or imported record that bypassed WordPress user-field sanitization.
		$stored_display_name = "  {$user_email}  ";
		$wpdb->update(
			$wpdb->users,
			array( 'display_name' => $stored_display_name ),
			array( 'ID' => $user_id )
		);
		clean_user_cache( $user_id );

		wp_set_current_user( $user_id );
		$this->prepare_account_details_request(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => $stored_display_name,
				'account_email'        => $user_email,
			)
		);

		$this->dispatch_account_details_save_expecting_redirect();

		$updated_user = get_userdata( $user_id );

		$this->assertSame( 'Jane', $updated_user->first_name, 'First name should be saved when cleaned display names match.' );
		$this->assertSame( 'Doe', $updated_user->last_name, 'Last name should be saved when cleaned display names match.' );
	}

	/**
	 * @testdox save_account_details() still blocks changing the display name to an email address.
	 *
	 * @covers WC_Form_Handler::save_account_details()
	 */
	public function test_save_account_details_blocks_new_email_like_display_name(): void {
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => 'display-name-customer',
				'user_email'   => 'display-name-customer@example.test',
				'first_name'   => 'Original',
				'last_name'    => 'Customer',
				'display_name' => 'Display Customer',
				'role'         => 'customer',
			)
		);

		wp_set_current_user( $user_id );
		$this->prepare_account_details_request(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => 'changed-display@example.test',
				'account_email'        => 'display-name-customer@example.test',
			)
		);

		WC_Form_Handler::save_account_details();

		$error_notices = wc_get_notices( 'error' );
		$updated_user  = get_userdata( $user_id );

		$this->assertCount( 1, $error_notices, 'Changing the display name to an email address should add one validation error.' );
		$this->assertSame( 'account_display_name', $error_notices[0]['data']['id'] ?? null, 'The validation error should identify the display-name field.' );
		$this->assertSame( 'Original', $updated_user->first_name, 'First name should not change when account validation fails.' );
		$this->assertSame( 'Customer', $updated_user->last_name, 'Last name should not change when account validation fails.' );
		$this->assertSame( 'Display Customer', $updated_user->display_name, 'Display name should not change to a new email-like value.' );
	}

	/**
	 * @testdox save_account_details() still allows changing the display name to a non-email value.
	 *
	 * @covers WC_Form_Handler::save_account_details()
	 */
	public function test_save_account_details_allows_non_email_display_name_change(): void {
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => 'display-name-customer',
				'user_email'   => 'display-name-customer@example.test',
				'display_name' => 'Display Customer',
				'role'         => 'customer',
			)
		);

		wp_set_current_user( $user_id );
		$this->prepare_account_details_request(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => 'Updated Customer',
				'account_email'        => 'display-name-customer@example.test',
			)
		);

		$this->dispatch_account_details_save_expecting_redirect();

		$updated_user = get_userdata( $user_id );

		$this->assertEmpty( wc_get_notices( 'error' ), 'A non-email display name should not add error notices.' );
		$this->assertSame( 'Updated Customer', $updated_user->display_name, 'Display name should save when changed to a non-email value.' );
	}

	/**
	 * @testdox add_to_cart_action() validates the same product ID it adds for each item of a grouped product form.
	 *
	 * @covers WC_Form_Handler::add_to_cart_action()
	 * @testWith ["%d"]
	 *           ["-%d"]
	 *           ["%dabc"]
	 *
	 * @param string $key_format Format of the quantity[] key naming the product.
	 */
	public function test_add_to_cart_action_rejects_protected_product_in_grouped_form( string $key_format ): void {
		$grouped   = WC_Helper_Product::create_grouped_product();
		$protected = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'            => $protected->get_id(),
				'post_password' => 'secret',
			)
		);
		WC()->cart->empty_cart();

		$validated_ids = array();
		$validation    = function ( $passed, $product_id ) use ( &$validated_ids ) {
			$validated_ids[] = $product_id;
			return $passed;
		};
		add_filter( 'woocommerce_add_to_cart_validation', $validation, 10, 2 );

		$_REQUEST = array(
			'add-to-cart' => (string) $grouped->get_id(),
			'quantity'    => array( sprintf( $key_format, $protected->get_id() ) => '1' ),
		);
		WC_Form_Handler::add_to_cart_action();

		remove_filter( 'woocommerce_add_to_cart_validation', $validation, 10 );

		$this->assertSame( array( $protected->get_id() ), $validated_ids, 'Validation should see the ID WC_Cart::add_to_cart() would add.' );
		$this->assertCount( 0, WC()->cart->get_cart(), 'The protected product should not be added to the cart.' );
		$this->assertSame( 1, wc_notice_count( 'error' ), 'The form should display the protected-product notice.' );
	}

	/**
	 * @testdox Payment authorization rejects other customers before any payment side effects.
	 * @testWith ["owner", true]
	 *           ["other", false]
	 *           ["guest", false]
	 * @param string $visitor Visitor identity.
	 * @param bool   $allowed Whether payment should reach the gateway.
	 */
	public function test_pay_action_checks_order_owner( string $visitor, bool $allowed ): void {
		$owner = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order = $this->create_payment_order( $owner );
		wp_set_current_user( 'owner' === $visitor ? $owner : ( 'other' === $visitor ? self::factory()->user->create( array( 'role' => 'customer' ) ) : 0 ) );

		$this->assert_payment_authorization( $order, $allowed );
	}

	/**
	 * @testdox A denied payment shows only the order-pay page's own reason.
	 * @testWith ["other", "This order cannot be paid for", 1]
	 *           ["logged_out", "Please log in to your account below", 0]
	 *           ["unverified_guest", "verify the email address associated with the order", 0]
	 * @param string $visitor Visitor identity.
	 * @param string $reason Text the page shows for this refusal.
	 * @param int    $refusals Times "This order cannot be paid for" should appear.
	 */
	public function test_denied_payment_page_shows_one_reason( string $visitor, string $reason, int $refusals ): void {
		$owner = self::factory()->user->create( array( 'role' => 'customer' ) );
		$order = $this->create_payment_order( 'unverified_guest' === $visitor ? 0 : $owner );
		wp_set_current_user( 'other' === $visitor ? self::factory()->user->create( array( 'role' => 'customer' ) ) : 0 );
		if ( 'unverified_guest' === $visitor ) {
			WC()->session = new WC_Mock_Session_Handler();
		}

		$this->assert_payment_authorization( $order, false );

		$original_query_vars                    = $GLOBALS['wp']->query_vars;
		$GLOBALS['wp']->query_vars['order-pay'] = $order->get_id();
		$_GET                                   = array(
			'pay_for_order' => 'true',
			'key'           => $order->get_order_key(),
		);
		try {
			ob_start();
			WC_Shortcode_Checkout::output( array() );
			$page = ob_get_clean();
		} finally {
			$GLOBALS['wp']->query_vars = $original_query_vars;
		}

		$this->assertStringContainsString( $reason, $page );
		$this->assertSame( $refusals, substr_count( $page, 'This order cannot be paid for' ) );
	}

	/**
	 * @testdox Verified payment proof survives storage and restoration through the production session handler.
	 */
	public function test_pay_action_restores_verified_guest_session(): void {
		wp_set_current_user( 0 );
		$original_cookies = $_COOKIE;
		$order            = $this->create_payment_order();
		$cookie_name      = '';
		$cookie_value     = '';
		$capture_cookie   = static function ( $enabled, $name, $value ) use ( &$cookie_name, &$cookie_value ) {
			if ( false !== strpos( $name, 'woocommerce_session' ) ) {
				$cookie_name  = $name;
				$cookie_value = $value;
			}
			return false;
		};
		add_filter( 'woocommerce_set_cookie_enabled', $capture_cookie, 10, 3 );

		try {
			$_COOKIE      = array();
			WC()->session = new WC_Session_Handler();
			WC()->session->init_session_cookie();
			WC()->session->set_customer_session_cookie( true );
			$this->assertNotSame( '', $cookie_value, 'The production handler should issue a signed session cookie.' );
			$this->assertFalse( \Automattic\WooCommerce\Internal\Utilities\Users::should_user_verify_order_email( $order->get_id(), $order->get_billing_email(), 'order-pay' ) );
			$customer_id = WC()->session->get_customer_id();
			WC()->session->save_data();
			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id, WC_SESSION_CACHE_GROUP );

			// Emulate the next request with the signed cookie and a fresh handler loading the database.
			$_COOKIE[ $cookie_name ] = $cookie_value;
			WC()->session            = new WC_Session_Handler();
			WC()->session->init_session_cookie();
			$this->assertSame( $customer_id, WC()->session->get_customer_id() );
			$this->assertIsArray( WC()->session->get( 'order_pay_email_verification' ) );
			$this->assert_payment_authorization( $order, true );
		} finally {
			WC()->session->destroy_session();
			$_COOKIE = $original_cookies;
			remove_filter( 'woocommerce_set_cookie_enabled', $capture_cookie );
		}
	}

	/**
	 * @testdox Guest payment requires a valid challenge carried forward to the separate payment form.
	 * @testWith ["verified", true]
	 *           ["unverified", false]
	 *           ["wrong_email", false]
	 *           ["wrong_nonce", false]
	 *           ["changed_key", false]
	 *           ["changed_email", false]
	 *           ["other_order", false]
	 *           ["expired", false]
	 *           ["invalid_proof", false]
	 *           ["other_session", false]
	 *           ["required_filter", false]
	 *           ["grace_period", true]
	 *           ["grace_disabled", false]
	 *           ["no_email", true]
	 *           ["verification_disabled", true]
	 *           ["session_email", true]
	 * @param string $scenario Verification scenario.
	 * @param bool   $allowed Whether payment should reach the gateway.
	 */
	public function test_pay_action_checks_guest_verification( string $scenario, bool $allowed ): void {
		wp_set_current_user( 0 );
		WC()->session = new WC_Mock_Session_Handler();
		$order        = $this->create_payment_order();

		if ( ! in_array( $scenario, array( 'unverified', 'grace_period', 'grace_disabled', 'no_email', 'verification_disabled', 'session_email' ), true ) ) {
			$_POST                     = array(
				'check_submission' => 'wrong_nonce' === $scenario ? 'invalid' : wp_create_nonce( 'wc_verify_email' ),
				'email'            => 'wrong_email' === $scenario ? 'other@example.org' : strtoupper( $order->get_billing_email() ),
			);
			$original_query_vars       = $GLOBALS['wp']->query_vars;
			$original_customer         = WC()->customer;
			WC()->customer             = new WC_Customer( 0, true );
			$GLOBALS['wp']->query_vars = array( 'order-pay' => $order->get_id() );
			$_GET                      = array(
				'pay_for_order' => 'true',
				'key'           => $order->get_order_key(),
			);
			ob_start();
			try {
				WC_Shortcode_Checkout::output( array() );
				$output = (string) ob_get_contents();
			} finally {
				ob_end_clean();
				$GLOBALS['wp']->query_vars = $original_query_vars;
				WC()->customer             = $original_customer;
			}
			if ( in_array( $scenario, array( 'wrong_email', 'wrong_nonce' ), true ) ) {
				$this->assertStringContainsString( 'woocommerce-verify-email', $output );
				$this->assertNull( WC()->session->get( 'order_pay_email_verification' ) );
			} else {
				$this->assertStringContainsString( 'id="order_review"', $output );
				$this->assertIsArray( WC()->session->get( 'order_pay_email_verification' ) );
			}
		}

		switch ( $scenario ) {
			case 'no_email':
				$order->set_billing_email( '' );
				break;
			case 'invalid_proof':
				WC()->session->set(
					'order_pay_email_verification',
					array(
						'hash'    => 'invalid',
						'expires' => time() + HOUR_IN_SECONDS,
					)
				);
				break;
			case 'changed_key':
				$order->set_order_key( 'wc_order_replaced_key' );
				break;
			case 'changed_email':
				$order->set_billing_email( 'changed@example.org' );
				break;
			case 'other_order':
				$order = $this->create_payment_order();
				break;
			case 'expired':
				$proof            = WC()->session->get( 'order_pay_email_verification' );
				$proof['expires'] = time() - 1;
				WC()->session->set( 'order_pay_email_verification', $proof );
				break;
			case 'other_session':
				$proof        = WC()->session->get( 'order_pay_email_verification' );
				WC()->session = $this->getMockBuilder( WC_Mock_Session_Handler::class )->onlyMethods( array( 'get_customer_id' ) )->getMock();
				WC()->session->method( 'get_customer_id' )->willReturn( 'different-session' );
				WC()->session->set( 'order_pay_email_verification', $proof );
				break;
			case 'required_filter':
				add_filter( 'woocommerce_order_email_verification_required', '__return_true' );
				break;
			case 'grace_disabled':
				add_filter(
					'woocommerce_order_email_verification_grace_period',
					static function () {
						return -1;
					}
				);
				// Fall through to create a new order with verification still required.
			case 'grace_period':
				$order->set_date_created( time() );
				break;
			case 'verification_disabled':
				add_filter( 'woocommerce_order_email_verification_required', '__return_false' );
				break;
			case 'session_email':
				WC()->session->set(
					'customer',
					array(
						'id'            => '0',
						'date_modified' => '',
						'email'         => $order->get_billing_email(),
					)
				);
				break;
		}
		$order->save();

		if ( 'verified' === $scenario ) {
			$this->assertTrue( \Automattic\WooCommerce\Internal\Utilities\Users::should_user_verify_order_email( $order->get_id(), null, 'order-received' ), 'Payment proof must not authorize another context.' );
		}
		$this->assert_payment_authorization( $order, $allowed );
	}

	/**
	 * @testdox Invalid order identifiers, keys and payment states never reach payment hooks or gateways.
	 * @testWith ["missing_id"]
	 *           ["zero_id"]
	 *           ["invalid_id"]
	 *           ["invalid_key"]
	 *           ["paid"]
	 * @param string $scenario Invalid payment request.
	 */
	public function test_pay_action_rejects_invalid_orders( string $scenario ): void {
		$order = $this->create_payment_order();
		if ( 'paid' === $scenario ) {
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
		}
		$this->assert_payment_authorization( $order, false, $scenario );
	}

	/**
	 * Create a payable order outside the email verification grace period.
	 *
	 * @param int $customer_id Order owner, or zero for a guest.
	 * @return WC_Order
	 */
	private function create_payment_order( int $customer_id = 0 ): WC_Order {
		$order = new WC_Order();
		$order->set_customer_id( $customer_id );
		$order->set_billing_email( 'payer@example.org' );
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_city( 'New York' );
		$order->set_billing_postcode( '10001' );
		$order->set_date_created( time() - DAY_IN_SECONDS );
		$order->set_payment_method( 'bacs' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();
		$order->add_product( $product, 1 );
		$order->set_total( 10 );
		$order->save();
		return $order;
	}

	/**
	 * Dispatch a payment POST and assert the gateway boundary and denied-request state.
	 *
	 * @param WC_Order $order Order to pay.
	 * @param bool     $allowed Whether payment is authorized.
	 * @param string   $scenario Optional invalid request scenario.
	 */
	private function assert_payment_authorization( WC_Order $order, bool $allowed, string $scenario = '' ): void {
		$gateway     = $this->getMockBuilder( WC_Payment_Gateway::class )
			->onlyMethods( array( 'validate_fields', 'process_payment' ) )
			->getMockForAbstractClass();
		$gateway->id = 'authorization_test';
		$gateway->expects( $allowed ? $this->once() : $this->never() )->method( 'validate_fields' );
		$gateway->expects( $allowed ? $this->once() : $this->never() )->method( 'process_payment' )
			->with( $order->get_id() )->willReturn( array( 'result' => 'failure' ) );
		$gateway_selections = 0;
		add_filter(
			'woocommerce_available_payment_gateways',
			static function () use ( $gateway, &$gateway_selections ) {
				++$gateway_selections;
				return array( $gateway->id => $gateway );
			}
		);

		$original_customer   = WC()->customer;
		$original_query_vars = $GLOBALS['wp']->query_vars;
		WC()->customer       = new WC_Customer( 0, true );
		WC()->customer->set_billing_country( 'CA' );
		WC()->customer->set_billing_state( 'ON' );
		WC()->customer->set_billing_city( 'Toronto' );
		WC()->customer->set_billing_postcode( 'M5V 1A1' );
		WC()->customer->save();
		$customer_before  = WC()->session->get( 'customer' );
		$before_actions   = did_action( 'woocommerce_before_pay_action' );
		$after_actions    = did_action( 'woocommerce_after_pay_action' );
		$status           = $order->get_status();
		$stock_reduced    = $order->get_data_store()->get_stock_reduced( $order->get_id() );
		$stock_quantities = array();
		foreach ( $order->get_items() as $item ) {
			$stock_quantities[ $item->get_product_id() ] = $item->get_product()->get_stock_quantity();
		}

		$GLOBALS['wp']->query_vars['order-pay'] = $order->get_id();
		if ( 'missing_id' === $scenario ) {
			unset( $GLOBALS['wp']->query_vars['order-pay'] );
		} elseif ( 'zero_id' === $scenario ) {
			$GLOBALS['wp']->query_vars['order-pay'] = 0;
		} elseif ( 'invalid_id' === $scenario ) {
			$GLOBALS['wp']->query_vars['order-pay'] = PHP_INT_MAX;
		}
		$_GET         = array( 'key' => 'invalid_key' === $scenario ? 'invalid' : $order->get_order_key() );
		$_POST        = array(
			'woocommerce_pay' => '1',
			'payment_method'  => $gateway->id,
		);
		$_REQUEST     = array( 'woocommerce-pay-nonce' => wp_create_nonce( 'woocommerce-pay' ) );
		$buffer_level = ob_get_level();
		try {
			WC_Form_Handler::pay_action();
			$this->assertSame( $allowed ? 1 : 0, $gateway_selections );
			$this->assertSame( $before_actions + ( $allowed ? 1 : 0 ), did_action( 'woocommerce_before_pay_action' ) );
			$this->assertSame( $after_actions + ( $allowed ? 1 : 0 ), did_action( 'woocommerce_after_pay_action' ) );
			if ( ! $allowed ) {
				$this->assertSame( 0, wc_notice_count( 'error' ), 'The order-pay page explains a denied payment; the handler must not queue a second notice.' );
				$this->assertSame( $customer_before, WC()->session->get( 'customer' ), 'Denied payment must not copy the order billing location into the session.' );
				$saved_order = wc_get_order( $order->get_id() );
				$this->assertSame( 'bacs', $saved_order->get_payment_method() );
				$this->assertSame( $status, $saved_order->get_status() );
				$this->assertSame( $stock_reduced, $saved_order->get_data_store()->get_stock_reduced( $order->get_id() ) );
				foreach ( $saved_order->get_items() as $item ) {
					$this->assertSame( $stock_quantities[ $item->get_product_id() ], $item->get_product()->get_stock_quantity() );
				}
			}
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			WC()->customer             = $original_customer;
			$GLOBALS['wp']->query_vars = $original_query_vars;
		}
	}

	/**
	 * Prepares request globals for the account details handler.
	 *
	 * @param array<string,string> $fields Account detail fields.
	 */
	private function prepare_account_details_request( array $fields ): void {
		$nonce = wp_create_nonce( 'save_account_details' );

		$_POST    = array_merge(
			array(
				'action' => 'save_account_details',
			),
			$fields
		);
		$_REQUEST = array(
			'save-account-details-nonce' => $nonce,
		);
	}

	/**
	 * Prepares a cancel-order request from the public order URL.
	 *
	 * @param WC_Order $order    Order to cancel.
	 * @param string   $redirect Optional redirect URL.
	 */
	private function prepare_cancel_order_request( WC_Order $order, string $redirect = '' ): void {
		$url   = $order->get_cancel_order_url_raw( $redirect );
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		parse_str( (string) $query, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The test builds a signed cancellation request.
		$_SERVER['REQUEST_URI'] = wp_make_link_relative( $url );
	}

	/**
	 * Dispatches the cancel-order handler and its redirect callback in registration order and expects a redirect.
	 *
	 * @param string $expected_redirect Expected redirect URL.
	 */
	private function dispatch_cancel_order_expecting_redirect( string $expected_redirect ): void {
		global $wp_current_filter;

		$current_filter_backup = $wp_current_filter;
		$wp_current_filter[]   = 'wp_loaded'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The test dispatches the handler in its registered action context.

		try {
			WC_Form_Handler::cancel_order();
			WC_Form_Handler::redirect_after_cancel_order();
		} catch ( RuntimeException $e ) {
			$this->assertSame( $expected_redirect, $e->getMessage(), 'The cancellation request should redirect to a clean URL.' );
			return;
		} finally {
			$wp_current_filter = $current_filter_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action stack after the simulated dispatch.
		}

		$this->fail( 'Expected the cancellation request to redirect after handling.' );
	}

	/**
	 * Dispatches only the redirect callback and expects a redirect.
	 *
	 * @param string $expected_redirect Expected redirect URL.
	 */
	private function dispatch_redirect_after_cancel_order_expecting_redirect( string $expected_redirect ): void {
		try {
			WC_Form_Handler::redirect_after_cancel_order();
		} catch ( RuntimeException $e ) {
			$this->assertSame( $expected_redirect, $e->getMessage(), 'The redirect callback should redirect to a clean URL.' );
			return;
		}

		$this->fail( 'Expected redirect_after_cancel_order() to redirect after cancel_order() handled the request.' );
	}

	/**
	 * Resets the handled order ID cancel_order() leaves behind so it cannot leak into the next test.
	 */
	private function reset_cancel_order_handled_flag(): void {
		$handled_ids = new ReflectionProperty( WC_Form_Handler::class, 'handled_cancel_order_ids' );
		$handled_ids->setAccessible( true );
		$handled_ids->setValue( null, array() );

		$pending = new ReflectionProperty( WC_Form_Handler::class, 'cancel_order_redirect_pending' );
		$pending->setAccessible( true );
		$pending->setValue( null, false );
	}

	/**
	 * Prepares request globals for the address handler.
	 *
	 * @param string               $address_type Address type.
	 * @param array<string,string> $fields       Unprefixed address fields.
	 */
	private function prepare_address_request( string $address_type, array $fields ): void {
		$prefixed_fields = array();

		foreach ( $fields as $field => $value ) {
			$prefixed_fields[ $address_type . '_' . $field ] = $value;
		}

		$_POST    = array_merge(
			array(
				'action' => 'edit_address',
			),
			$prefixed_fields
		);
		$_REQUEST = array(
			'woocommerce-edit-address-nonce' => wp_create_nonce( 'woocommerce-edit_address' ),
		);
	}

	/**
	 * Dispatches the account-details save handler and expects its success redirect.
	 */
	private function dispatch_account_details_save_expecting_redirect(): void {
		try {
			WC_Form_Handler::save_account_details();
		} catch ( RuntimeException $e ) {
			$this->assertSame(
				wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ),
				$e->getMessage(),
				'Successful account saves should redirect to Account details.'
			);
			return;
		}

		$this->fail( 'Expected save_account_details() to redirect after a successful save.' );
	}

	/**
	 * Dispatches the address handler and expects its success redirect.
	 */
	private function dispatch_address_save_expecting_redirect(): void {
		try {
			WC_Form_Handler::save_address();
		} catch ( RuntimeException $e ) {
			$this->assertSame(
				wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ),
				$e->getMessage(),
				'Successful address saves should redirect to Addresses.'
			);
			return;
		}

		$this->fail( 'Expected save_address() to redirect after a successful save. Notices: ' . wp_json_encode( wc_get_notices() ) );
	}
}
