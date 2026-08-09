<?php
/**
 * Tests for WC_Form_Handler.
 *
 * @package WooCommerce\Tests\FormHandler
 */

declare( strict_types = 1 );

/**
 * WC_Form_Handler tests.
 */
class WC_Form_Handler_Test extends WC_Unit_Test_Case {

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
	 * @testdox add_to_cart_action() does not redirect when error notices exist, unless the woocommerce_add_to_cart_should_redirect filter overrides it.
	 *
	 * @covers WC_Form_Handler::add_to_cart_action()
	 */
	public function test_add_to_cart_action_respects_error_notices_and_should_redirect_filter(): void {
		update_option( 'woocommerce_cart_redirect_after_add', 'yes' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product();

		$_REQUEST['add-to-cart'] = $product->get_id();
		$_REQUEST['quantity']    = 1;
		$_POST['quantity']       = 1;

		// Simulate a stale error notice from a previous request (see #37164).
		wc_add_notice( 'An unrelated error.', 'error' );

		// Default behavior: the stale error notice blocks the redirect, but the product is added.
		WC_Form_Handler::add_to_cart_action( false );

		$this->assertTrue( wc_notice_count( 'error' ) > 0, 'The error notice should still be present.' );
		$this->assertCount( 1, WC()->cart->get_cart(), 'The product should still be added to the cart.' );

		// The filter should allow forcing the redirect even when error notices exist.
		WC()->cart->empty_cart();
		wc_clear_notices();

		$_REQUEST['add-to-cart'] = $product->get_id();
		wc_add_notice( 'Another unrelated error.', 'error' );

		add_filter(
			'woocommerce_add_to_cart_should_redirect',
			static function ( $_should_redirect, $_product ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				return true;
			},
			10,
			2
		);

		try {
			WC_Form_Handler::add_to_cart_action( false );
			$this->fail( 'Expected add_to_cart_action() to redirect when the filter forces it.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( wc_get_cart_url(), $e->getMessage(), 'The redirect should target the cart URL.' );
		}

		remove_all_filters( 'woocommerce_add_to_cart_should_redirect' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );

		unset( $_REQUEST['add-to-cart'], $_REQUEST['quantity'], $_POST['quantity'] );
		$product->delete( true );
	}

	/**
	 * @testdox add_to_cart_action() does not redirect when the woocommerce_add_to_cart_should_redirect filter returns false, even when redirect is enabled.
	 *
	 * @covers WC_Form_Handler::add_to_cart_action()
	 */
	public function test_add_to_cart_action_filter_can_prevent_redirect(): void {
		update_option( 'woocommerce_cart_redirect_after_add', 'yes' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product();

		$_REQUEST['add-to-cart'] = $product->get_id();
		$_REQUEST['quantity']    = 1;
		$_POST['quantity']       = 1;

		add_filter(
			'woocommerce_add_to_cart_should_redirect',
			static function ( $_should_redirect, $_product ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				return false;
			},
			10,
			2
		);

		WC_Form_Handler::add_to_cart_action( false );

		$this->assertCount( 1, WC()->cart->get_cart(), 'The product should still be added to the cart.' );

		remove_all_filters( 'woocommerce_add_to_cart_should_redirect' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );

		unset( $_REQUEST['add-to-cart'], $_REQUEST['quantity'], $_POST['quantity'] );
		$product->delete( true );
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
