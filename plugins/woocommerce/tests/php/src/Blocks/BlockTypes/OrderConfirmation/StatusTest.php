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
	 * @testdox The block filter pipeline preserves order context, legacy arguments, and legacy precedence.
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

		$this->assertSame( self::BLOCK_FAILED_MESSAGE, $new_filter_message, 'The new filter should receive the exact block default.' );
		$this->assertSame( $order, $new_filter_order, 'The new filter should receive the exact failed order.' );
		$this->assertCount( 2, $legacy_filter_arguments, 'The legacy filter should continue receiving two arguments.' );
		$this->assertSame( $new_filter_result, $legacy_filter_arguments[0], 'The legacy filter should receive the validated new-filter result.' );
		$this->assertNull( $legacy_filter_arguments[1], 'The legacy filter second argument should remain null.' );
		$this->assertStringContainsString( $legacy_filter_result, $html, 'The legacy filter should retain final output control.' );
		$this->assertStringNotContainsString( $new_filter_result, $html, 'The legacy replacement should win final precedence.' );
	}

	/**
	 * @testdox An empty new block-filter result is passed unchanged to the legacy filter.
	 */
	public function test_empty_new_filter_result_is_passed_to_legacy_filter(): void {
		$legacy_filter_message = null;

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () {
				return '';
			}
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function ( $message ) use ( &$legacy_filter_message ) {
				$legacy_filter_message = $message;
				return '<strong>Legacy replacement</strong>';
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertSame( '', $legacy_filter_message, 'The legacy filter should receive the empty new-filter result.' );
		$this->assertStringContainsString( '<strong>Legacy replacement</strong>', $html, 'The legacy filter should retain final control after an empty new-filter result.' );
	}

	/**
	 * @testdox An invalid new block-filter result falls back before the legacy filter runs.
	 */
	public function test_invalid_new_filter_return_falls_back_to_block_default(): void {
		$legacy_filter_message = null;

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () {
				return array( 'invalid' );
			}
		);
		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function ( $message ) use ( &$legacy_filter_message ) {
				$legacy_filter_message = $message;
				return $message;
			}
		);

		$html = $this->render_failed_order( $this->create_failed_order() );

		$this->assertSame( self::BLOCK_FAILED_MESSAGE, $legacy_filter_message, 'The legacy filter should receive the block default after new-filter fallback.' );
		$this->assertStringContainsString( self::BLOCK_FAILED_MESSAGE, $html, 'The block default should render after new-filter fallback.' );
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
	 * @testdox An empty legacy block-filter result remains the final message.
	 */
	public function test_empty_legacy_filter_result_remains_empty(): void {
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
}
