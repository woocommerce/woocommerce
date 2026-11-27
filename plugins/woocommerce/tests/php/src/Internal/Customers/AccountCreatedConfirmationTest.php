<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Customers;

use Automattic\WooCommerce\Internal\Customers\AccountCreatedConfirmation;
use Automattic\WooCommerce\Internal\Customers\NewAccountEmailWarning;
use Automattic\WooCommerce\Internal\Customers\NewCustomerSession;
use WC_Unit_Test_Case;

/**
 * Tests for the AccountCreatedConfirmation class.
 */
class AccountCreatedConfirmationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AccountCreatedConfirmation
	 */
	private $sut;

	/**
	 * The New account email, so its enabled state can be set and restored.
	 *
	 * @var \WC_Email
	 */
	private $email;

	/**
	 * The email's enabled state before the test.
	 *
	 * @var string
	 */
	private $original_enabled;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new AccountCreatedConfirmation();
		$this->sut->init( wc_get_container()->get( NewCustomerSession::class ), new NewAccountEmailWarning() );
		$this->email            = WC()->mailer()->get_emails()['WC_Email_Customer_New_Account'];
		$this->original_enabled = $this->email->enabled;
		$this->email->enabled   = 'yes';
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->email->enabled = $this->original_enabled;
		parent::tearDown();
	}

	/**
	 * @testdox The confirmation names the inbox and links to the page for requesting a new link.
	 */
	public function test_render_names_inbox_and_links_to_new_link_page(): void {
		$html = $this->sut->render( 'shopper@example.test' );

		$this->assertStringContainsString( 'Your account has been created.', $html );
		$this->assertStringContainsString( 'Check <strong>shopper@example.test</strong> for a link to set your password.', $html );
		$this->assertStringContainsString( '<a href="' . esc_url( wc_lostpassword_url() ) . '">Get a new link</a>', $html );
	}

	/**
	 * @testdox With the New account email off, the confirmation does not send the shopper to their inbox.
	 */
	public function test_render_without_new_account_email_links_to_lost_password(): void {
		$this->email->enabled = 'no';

		$html = $this->sut->render( 'shopper@example.test' );

		$this->assertStringNotContainsString( 'Check', $html, 'No email is sent, so the shopper should not wait for one.' );
		$this->assertStringContainsString( '<a href="' . esc_url( wc_lostpassword_url() ) . '">get a link to set your password</a>', $html );
	}

	/**
	 * @testdox The email address is escaped.
	 */
	public function test_render_escapes_email(): void {
		$this->assertStringNotContainsString( '<script>', $this->sut->render( '<script>@example.test' ) );
	}

	/**
	 * @testdox The order received page shows the confirmation only for an order whose new account is unclaimed.
	 */
	public function test_order_received_shows_confirmation_only_for_unclaimed_account(): void {
		wp_set_current_user( 0 );
		$new_customer_session = wc_get_container()->get( NewCustomerSession::class );
		$customer_id          = $new_customer_session->create_customer( 'order-received-new@example.test' );
		$new_order            = wc_create_order( array( 'customer_id' => $customer_id ) );
		$new_order->set_billing_email( 'order-received-new@example.test' );
		$new_customer_session->mark_order_created_customer( $new_order, $customer_id );
		$new_order->save();
		$guest_order = wc_create_order();

		$this->assertStringContainsString( '<strong>order-received-new@example.test</strong>', $this->capture_order_received( $new_order->get_id() ) );
		$this->assertSame( '', $this->capture_order_received( $guest_order->get_id() ), 'A guest order has no new account.' );

		do_action( 'set_auth_cookie', '', 0, 0, $customer_id, 'auth', '' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertSame( '', $this->capture_order_received( $new_order->get_id() ), 'Once the account is claimed, there is nothing left to set up.' );
	}

	/**
	 * @testdox Nothing is shown above the My Account forms unless the shopper just registered.
	 */
	public function test_output_after_registration_only_after_registering(): void {
		ob_start();
		$this->sut->output_after_registration();
		$this->assertSame( '', ob_get_clean(), 'Nothing should show before registering.' );

		$this->sut->show_after_registration( 'new@example.test' );
		ob_start();
		$this->sut->output_after_registration();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'wc-block-order-confirmation-create-account', $html, 'The box should use the order confirmation styles.' );
		$this->assertStringContainsString( '<strong>new@example.test</strong>', $html );
	}

	/**
	 * Capture the confirmation output for the order received page.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private function capture_order_received( int $order_id ): string {
		ob_start();
		$this->sut->output_on_order_received( $order_id );
		return (string) ob_get_clean();
	}
}
