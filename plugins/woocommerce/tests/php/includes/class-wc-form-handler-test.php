<?php
/**
 * Tests for WC_Form_Handler.
 *
 * @package WooCommerce\Tests\FormHandler
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
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
	 * IDs of additional checkout fields registered by the current test.
	 *
	 * @var string[]
	 */
	private array $registered_checkout_fields = array();

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

		foreach ( $this->registered_checkout_fields as $field_id ) {
			__internal_woocommerce_blocks_deregister_checkout_field( $field_id );
		}

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
	 * @testdox copy_address() copies the saved $source_type address into the empty $target_type address.
	 *
	 * @testWith ["billing", "shipping"]
	 *           ["shipping", "billing"]
	 * @covers WC_Form_Handler::copy_address()
	 *
	 * @param string $source_type Address type copied from.
	 * @param string $target_type Address type copied into.
	 */
	public function test_copy_address_copies_saved_address_into_empty_address( string $source_type, string $target_type ): void {
		$customer  = $this->create_customer_with_address( $source_type );
		$hook_args = null;
		add_action(
			'woocommerce_customer_copy_address',
			function ( ...$args ) use ( &$hook_args ) {
				$hook_args = $args;
			},
			10,
			4
		);

		$this->prepare_copy_address_request( $target_type );
		$this->dispatch_copy_address_expecting_redirect();

		$customer = new WC_Customer( $customer->get_id() );

		$this->assertEmpty( wc_get_notices( 'error' ), 'Copying a saved address should not add error notices.' );
		$this->assertSame( 'Address copied successfully.', wc_get_notices( 'success' )[0]['notice'] ?? null );
		foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' ) as $field ) {
			$this->assertSame(
				$customer->{"get_{$source_type}_{$field}"}(),
				$customer->{"get_{$target_type}_{$field}"}(),
				"The {$target_type}_{$field} value should match the copied {$source_type} address."
			);
		}
		$this->assertSame( array( $customer->get_id(), $target_type, $source_type ), array_slice( $hook_args ?? array(), 0, 3 ), 'The copy hook should fire with the user ID and address types.' );
	}

	/**
	 * @testdox copy_address() uses the account email when copying a shipping address into billing.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_uses_account_email_for_billing(): void {
		$customer = $this->create_customer_with_address( 'shipping' );

		$this->prepare_copy_address_request( 'billing' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( 'copy-address-customer@example.test', ( new WC_Customer( $customer->get_id() ) )->get_billing_email() );
	}

	/**
	 * @testdox copy_address() leaves an address that is already set up untouched.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_does_not_overwrite_existing_address(): void {
		$customer = $this->create_customer_with_address( 'billing' );
		$customer->set_shipping_address_1( '1 Existing Road' );
		$customer->set_shipping_country( 'US' );
		$customer->save();

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( '1 Existing Road', ( new WC_Customer( $customer->get_id() ) )->get_shipping_address_1(), 'An existing shipping address should not be overwritten.' );
		$this->assertEmpty( wc_get_notices( 'success' ), 'No success notice should be shown when nothing was copied.' );
	}

	/**
	 * @testdox copy_address() rejects a request with an invalid nonce.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_rejects_invalid_nonce(): void {
		$customer = $this->create_customer_with_address( 'billing' );

		$this->prepare_copy_address_request( 'shipping' );
		$_REQUEST['_wpnonce'] = 'invalid';
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertCount( 1, wc_get_notices( 'error' ), 'An invalid nonce should add an error notice.' );
		$this->assertSame( '', ( new WC_Customer( $customer->get_id() ) )->get_shipping_address_1(), 'An invalid nonce should not copy the address.' );
	}

	/**
	 * @testdox copy_address() refuses to copy a billing address whose country the store does not ship to.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_rejects_country_not_available_for_shipping(): void {
		$customer = $this->create_customer_with_address( 'billing' );
		add_filter(
			'woocommerce_countries_shipping_countries',
			function () {
				return array( 'CA' => 'Canada' );
			}
		);

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertCount( 1, wc_get_notices( 'error' ), 'A country the store does not ship to should add an error notice.' );
		$this->assertSame( '', ( new WC_Customer( $customer->get_id() ) )->get_shipping_address_1(), 'The address should not be copied to an unavailable country.' );
	}

	/**
	 * @testdox copy_address() copies the stored address values, not the values returned by getter filters.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_ignores_getter_filters(): void {
		$customer = $this->create_customer_with_address( 'billing' );
		add_filter(
			'woocommerce_customer_get_billing_address_1',
			function () {
				return 'Filtered street';
			}
		);

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( '123 Market Street', ( new WC_Customer( $customer->get_id() ) )->get_shipping_address_1( 'edit' ), 'The stored billing street should be copied, not the filtered one.' );
	}

	/**
	 * @testdox copy_address() does nothing for a logged-out visitor.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_ignores_logged_out_request(): void {
		$this->prepare_copy_address_request( 'shipping' );

		WC_Form_Handler::copy_address();

		$this->assertEmpty( wc_get_notices(), 'A logged-out request should not add notices.' );
	}

	/**
	 * @testdox copy_address() rejects an address type other than billing or shipping.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_rejects_unknown_address_type(): void {
		$this->create_customer_with_address( 'billing' );

		$this->prepare_copy_address_request( 'other' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( 'The address could not be copied. Please try again.', wc_get_notices( 'error' )[0]['notice'] ?? null );
		$this->assertEmpty( wc_get_notices( 'success' ), 'An unknown address type should not show a success notice.' );
	}

	/**
	 * @testdox copy_address() does nothing when there is no saved address to copy from.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_does_nothing_without_source_address(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertEmpty( wc_get_notices(), 'Nothing to copy should not add notices.' );
		$this->assertSame( '', ( new WC_Customer( $user_id ) )->get_shipping_country(), 'No shipping address should be saved.' );
	}

	/**
	 * @testdox copy_address() refuses to copy when the target address requires a field the source address lacks.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_rejects_missing_required_target_field(): void {
		$customer = $this->create_customer_with_address( 'shipping' );
		$customer->set_shipping_phone( '' );
		$customer->save();
		add_filter(
			'woocommerce_billing_fields',
			function ( $fields ) {
				$fields['billing_phone']['required'] = true;
				return $fields;
			}
		);

		$this->prepare_copy_address_request( 'billing' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( 'Phone is a required field.', wc_get_notices( 'error' )[0]['notice'] ?? null );
		$this->assert_copy_address_rejected( $customer->get_id(), 'billing' );
	}

	/**
	 * @testdox copy_address() refuses to copy a postcode that is not valid for the address country.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_rejects_invalid_postcode(): void {
		$customer = $this->create_customer_with_address( 'billing' );
		$customer->set_billing_postcode( 'NOT-A-ZIP' );
		$customer->save();

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( 'Please enter a valid postcode / ZIP.', wc_get_notices( 'error' )[0]['notice'] ?? null );
		$this->assert_copy_address_rejected( $customer->get_id(), 'shipping' );
	}

	/**
	 * @testdox copy_address() copies additional address fields registered through the Checkout block.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_copies_additional_checkout_fields(): void {
		$this->register_checkout_field(
			array(
				'id'       => 'test-namespace/gate-code',
				'label'    => 'Gate code',
				'location' => 'address',
			)
		);
		$customer        = $this->create_customer_with_address( 'billing' );
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$checkout_fields->persist_field_for_customer( 'test-namespace/gate-code', '1234', $customer, 'billing' );
		$customer->save();

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertEmpty( wc_get_notices( 'error' ), 'Copying additional fields should not add error notices.' );
		$this->assertSame( '1234', $checkout_fields->get_field_from_object( 'test-namespace/gate-code', new WC_Customer( $customer->get_id() ), 'shipping' ) );
	}

	/**
	 * @testdox copy_address() refuses to copy when a required Checkout block address field is empty.
	 *
	 * @covers WC_Form_Handler::copy_address()
	 */
	public function test_copy_address_rejects_missing_required_additional_checkout_field(): void {
		$this->register_checkout_field(
			array(
				'id'       => 'test-namespace/gate-code',
				'label'    => 'Gate code',
				'location' => 'address',
				'required' => true,
			)
		);
		$customer = $this->create_customer_with_address( 'billing' );

		$this->prepare_copy_address_request( 'shipping' );
		$this->dispatch_copy_address_expecting_redirect();

		$this->assertSame( '<strong>Gate code</strong> is required', wc_get_notices( 'error' )[0]['notice'] ?? null );
		$this->assert_copy_address_rejected( $customer->get_id(), 'shipping' );
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

	/**
	 * Creates the current customer with only one saved address type.
	 *
	 * @param string $address_type Address type to save.
	 * @return WC_Customer
	 */
	private function create_customer_with_address( string $address_type ): WC_Customer {
		$user_id = self::factory()->user->create(
			array(
				'user_login' => 'copy-address-customer',
				'user_email' => 'copy-address-customer@example.test',
				'role'       => 'customer',
			)
		);
		wp_set_current_user( $user_id );

		$customer = new WC_Customer( $user_id );
		foreach ( self::provide_address_types()[ $address_type ][1] as $field => $value ) {
			$customer->{"set_{$address_type}_{$field}"}( $value );
		}
		$customer->save();

		return $customer;
	}

	/**
	 * Prepares a copy address request for the given target address type.
	 *
	 * @param string $target_type Address type to copy into.
	 */
	private function prepare_copy_address_request( string $target_type ): void {
		$_GET     = array( 'copy-address' => $target_type );
		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'woocommerce-copy-address-' . $target_type ) );
	}

	/**
	 * Dispatches the copy address handler and expects a redirect back to Addresses.
	 */
	private function dispatch_copy_address_expecting_redirect(): void {
		try {
			WC_Form_Handler::copy_address();
		} catch ( RuntimeException $e ) {
			$this->assertSame(
				wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ),
				$e->getMessage(),
				'Copying an address should redirect to Addresses.'
			);
			return;
		}

		$this->fail( 'Expected copy_address() to redirect. Notices: ' . wp_json_encode( wc_get_notices() ) );
	}

	/**
	 * Asserts that a copy was refused: no success notice and the target address is still empty.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $target_type Address type the copy targeted.
	 */
	private function assert_copy_address_rejected( int $customer_id, string $target_type ): void {
		$this->assertEmpty( wc_get_notices( 'success' ), 'A refused copy should not show a success notice.' );
		$this->assertSame( '', ( new WC_Customer( $customer_id ) )->{"get_{$target_type}_address_1"}(), "The {$target_type} address should not be saved." );
	}

	/**
	 * Registers an additional checkout field for the current test.
	 *
	 * @param array $args Field arguments.
	 */
	private function register_checkout_field( array $args ): void {
		woocommerce_register_additional_checkout_field( $args );
		$this->registered_checkout_fields[] = $args['id'];
	}
}
