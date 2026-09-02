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

		$this->assertSame( 1, wc_notice_count( 'notice' ), 'The deferred redirect must not run for a request handled outside wp_loaded, so the notice stays in place.' );
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
}
