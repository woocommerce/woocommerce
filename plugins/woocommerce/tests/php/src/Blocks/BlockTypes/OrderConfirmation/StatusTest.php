<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Status as StatusBlock;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Gateway_Paypal;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the Order Confirmation Status block.
 */
class StatusTest extends WC_Unit_Test_Case {

	private const BLOCK_FAILED_MESSAGE = 'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.';

	/**
	 * Reset message filters before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		remove_all_filters( 'woocommerce_thankyou_order_failed_text' );
		remove_all_filters( 'woocommerce_thankyou_order_received_text' );
	}

	/**
	 * @testdox The block filter pipeline preserves order context, legacy arguments, and new-filter precedence.
	 */
	public function test_filter_pipeline_preserves_context_arguments_and_precedence(): void {
		$order                   = $this->create_failed_order();
		$new_filter_message      = null;
		$new_filter_order        = null;
		$legacy_filter_arguments = array();
		$new_filter_result       = '<strong>New failure context</strong>';
		$legacy_filter_result    = '<a href="https://example.com/legacy">Legacy replacement</a>';

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function ( $message, $filtered_order ) use ( &$new_filter_message, &$new_filter_order, $new_filter_result ) {
				$new_filter_message = $message;
				$new_filter_order   = $filtered_order;
				return $new_filter_result;
			},
			10,
			2
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function ( ...$arguments ) use ( &$legacy_filter_arguments, $legacy_filter_result ) {
				$legacy_filter_arguments = $arguments;
				return $legacy_filter_result;
			},
			10,
			2
		);

		$html = $this->render_failed_order( $order );

		$this->assertCount( 2, $legacy_filter_arguments, 'The legacy filter should continue receiving two arguments.' );
		$this->assertSame( self::BLOCK_FAILED_MESSAGE, $legacy_filter_arguments[0], 'The legacy filter should receive the untouched block default.' );
		$this->assertNull( $legacy_filter_arguments[1], 'The legacy filter second argument should remain null.' );
		$this->assertSame( $legacy_filter_result, $new_filter_message, 'The new filter should receive the validated legacy-filter result.' );
		$this->assertSame( $order, $new_filter_order, 'The new filter should receive the exact failed order.' );
		$this->assertStringContainsString( $new_filter_result, $html, 'The new filter should retain final output control.' );
		$this->assertStringNotContainsString( $legacy_filter_result, $html, 'The new-filter replacement should win final precedence.' );
	}

	/**
	 * @testdox An empty new block-filter result is the final message.
	 */
	public function test_empty_new_filter_result_is_final(): void {
		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () {
				return '';
			}
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function () {
				return '<strong>Legacy replacement</strong>';
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertStringNotContainsString( '<strong>Legacy replacement</strong>', $html, 'An empty new-filter result should override the legacy replacement.' );
		$this->assertMatchesRegularExpression(
			'/<p><\/p>\s*<p class="wc-block-order-confirmation-status__actions">/',
			$html,
			'The failed-message paragraph should be empty.'
		);
	}

	/**
	 * @testdox An invalid new block-filter result preserves the valid legacy-filter result.
	 */
	public function test_invalid_new_filter_return_preserves_legacy_filter_result(): void {
		$new_filter_message   = null;
		$legacy_filter_result = '<a href="https://example.com/legacy">Legacy replacement</a>';

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function ( $message ) use ( &$new_filter_message ) {
				$new_filter_message = $message;
				return array( 'invalid' );
			}
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function () use ( $legacy_filter_result ) {
				return $legacy_filter_result;
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertSame( $legacy_filter_result, $new_filter_message, 'The new filter should receive the validated legacy-filter result.' );
		$this->assertStringContainsString( $legacy_filter_result, $html, 'An invalid new-filter return should preserve the legacy-filter result, not the block default.' );
		$this->assertStringNotContainsString( self::BLOCK_FAILED_MESSAGE, $html, 'An invalid new-filter return should not discard valid legacy text for the block default.' );
	}

	/**
	 * @testdox An invalid legacy block-filter result falls back to the valid new-filter result.
	 */
	public function test_invalid_legacy_filter_return_falls_back_to_new_filter_result(): void {
		$new_filter_result = '<strong>New failure context</strong>';

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () use ( $new_filter_result ) {
				return $new_filter_result;
			}
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function () {
				return new \stdClass();
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertStringContainsString( $new_filter_result, $html, 'An invalid legacy return should restore the valid new-filter result.' );
	}

	/**
	 * @testdox An empty legacy block-filter result remains the final message when no new-filter callback runs.
	 */
	public function test_empty_legacy_filter_result_remains_empty(): void {
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function () {
				return '';
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertMatchesRegularExpression(
			'/<p><\/p>\s*<p class="wc-block-order-confirmation-status__actions">/',
			$html,
			'The failed-message paragraph should remain empty.'
		);
	}

	/**
	 * @testdox The new filter overrides a single-argument legacy callback that replaces the message unconditionally.
	 */
	public function test_single_argument_legacy_replacer_is_overridden_by_new_filter(): void {
		$new_filter_result    = '<strong>Merchant failure context</strong>';
		$legacy_filter_result = '<strong>Unconditional legacy replacement</strong>';

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () use ( $new_filter_result ) {
				return $new_filter_result;
			}
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function ( $text ) use ( $legacy_filter_result ) {
				unset( $text );
				return $legacy_filter_result;
			},
			11
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertStringContainsString( $new_filter_result, $html, 'The new filter should override an unconditional single-argument legacy replacer.' );
		$this->assertStringNotContainsString( $legacy_filter_result, $html, 'The unconditional legacy replacement should not survive the new filter.' );
	}

	/**
	 * @testdox The final block message keeps safe post HTML and removes unsafe markup.
	 */
	public function test_final_message_preserves_safe_post_html_and_removes_unsafe_markup(): void {
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function () {
				return '<strong>Retry</strong> or <a href="https://example.com/help" onclick="alert(1)">contact support</a><script>alert(1)</script>';
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertStringContainsString( '<strong>Retry</strong>', $html, 'Safe emphasis should remain.' );
		$this->assertStringContainsString( '<a href="https://example.com/help">contact support</a>', $html, 'A safe inline link should remain.' );
		$this->assertStringNotContainsString( 'onclick=', $html, 'Event handler attributes should be removed.' );
		$this->assertStringNotContainsString( '<script', $html, 'Script elements should be removed.' );
	}

	/**
	 * @testdox The PayPal success callback preserves the new failed-order result in the block pipeline.
	 */
	public function test_paypal_success_callback_does_not_replace_block_failed_message(): void {
		$new_filter_result = '<strong>Payment failed</strong>';
		$paypal_gateway    = new WC_Gateway_Paypal();

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () use ( $new_filter_result ) {
				return $new_filter_result;
			}
		);
		add_filter( 'woocommerce_thankyou_order_received_text', array( $paypal_gateway, 'order_received_text' ), 10, 2 );

		$html = $this->render_failed_order( $this->create_failed_order( WC_Gateway_Paypal::ID ) );

		$this->assertStringContainsString( $new_filter_result, $html, 'The PayPal callback should pass through the failure-specific result when its order argument is null.' );
	}

	/**
	 * Create a failed order for a block test.
	 *
	 * @param string $payment_method Payment method ID.
	 * @return WC_Order
	 */
	private function create_failed_order( string $payment_method = '' ): WC_Order {
		$order = new WC_Order();
		$order->set_status( OrderStatus::FAILED );

		if ( '' !== $payment_method ) {
			$order->set_payment_method( $payment_method );
		}

		$order->save();
		return $order;
	}

	/**
	 * Render a failed order with full permissions.
	 *
	 * @param WC_Order $order Order to render.
	 * @return string
	 */
	private function render_failed_order( WC_Order $order ): string {
		$sut = new class() extends StatusBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}

			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_content_proxy( $order, $permission ) {
				return $this->render_content( $order, $permission );
			}
		};

		return $sut->render_content_proxy( $order, 'full' );
	}

	/**
	 * @testdox A missing order directs the shopper to email and account confirmation paths.
	 */
	public function test_missing_order_notice(): void {
		$my_account_url = wc_get_page_permalink( 'myaccount' );
		$content        = $this->render_confirmation_notice( null );

		$this->assertNotSame( '', $my_account_url, 'The fixture must expose the My account login destination.' );
		$this->assertStringContainsString( 'If you&#039;ve just placed an order, give your email a quick check for the confirmation.', $content );
		$this->assertStringContainsString( 'Have an account with us?', $content );
		$this->assertStringContainsString( 'Log in here to view your order details', $content );
		$this->assertStringContainsString( 'href="' . esc_url( $my_account_url ) . '"', $content );
	}

	/**
	 * @testdox An existing customer order without a valid key directs the shopper to email and account confirmation paths.
	 * @dataProvider invalid_order_key_cases
	 *
	 * @param string $key_mode Missing or invalid key mode.
	 */
	public function test_invalid_key_notice_uses_real_permission_result( string $key_mode ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request globals are restored fixture state.
		$original_get     = $_GET;
		$original_user_id = get_current_user_id();
		$customer_id      = 0;
		$order            = null;

		try {
			$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
			$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
			$order->set_billing_email( 'status-shopper@example.com' );
			$order->save();
			wp_set_current_user( $customer_id );
			$_GET = array();

			if ( 'wrong' === $key_mode ) {
				$_GET['key'] = 'wc_order_wrong';
			}

			$permission     = $this->get_view_order_permissions( $order );
			$content        = $this->render_confirmation_notice( $order );
			$my_account_url = wc_get_page_permalink( 'myaccount' );

			$this->assertFalse( $permission, 'The real permission decision must reject a missing or invalid order key.' );
			$this->assertNotSame( '', $my_account_url, 'The fixture must expose the My account login destination.' );
			$this->assertStringContainsString( 'Great news! Your order has been received, and a confirmation will be sent to your email address.', $content );
			$this->assertStringContainsString( 'Have an account with us?', $content );
			$this->assertStringContainsString( 'Log in here', $content );
			$this->assertStringContainsString( 'to view your order.', $content );
			$this->assertStringContainsString( 'href="' . esc_url( $my_account_url ) . '"', $content );
		} finally {
			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
			wp_set_current_user( $original_user_id );
			$_GET = $original_get;
			if ( $customer_id ) {
				wp_delete_user( $customer_id );
			}
		}
	}

	/**
	 * Named invalid-key cases.
	 *
	 * @return array<string, array{string}>
	 */
	public static function invalid_order_key_cases(): array {
		return array(
			'missing order key' => array( 'missing' ),
			'wrong order key'   => array( 'wrong' ),
		);
	}

	/**
	 * Render a status notice through the real Status block owner.
	 *
	 * @param WC_Order|null $order Order object, or null when none exists.
	 * @return string
	 */
	private function render_confirmation_notice( ?WC_Order $order ): string {
		return $this->create_proxy()->render_confirmation_notice_proxy( $order );
	}

	/**
	 * Resolve view permission through the real Status block owner.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|false
	 */
	private function get_view_order_permissions( WC_Order $order ) {
		return $this->create_proxy()->get_view_order_permissions_proxy( $order );
	}

	/**
	 * Create a public proxy over the Status block's protected owners.
	 *
	 * @return StatusBlock
	 */
	private function create_proxy(): StatusBlock {
		return new class() extends StatusBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_confirmation_notice_proxy( ?WC_Order $order ): string {
				return $this->render_confirmation_notice( $order );
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function get_view_order_permissions_proxy( WC_Order $order ) {
				return $this->get_view_order_permissions( $order );
			}
		};
	}
}
