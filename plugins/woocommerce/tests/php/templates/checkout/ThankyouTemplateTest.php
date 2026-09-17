<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Templates\Checkout;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the checkout thankyou template.
 */
class ThankyouTemplateTest extends WC_Unit_Test_Case {

	private const CLASSIC_FAILED_MESSAGE = 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.';

	/**
	 * @testdox The classic failure filter receives the exact default and order, then replaces the message.
	 */
	public function test_failed_message_filter_replaces_text_and_receives_order(): void {
		$order            = $this->create_failed_order();
		$received_message = null;
		$received_order   = null;

		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function ( $message, $filtered_order ) use ( &$received_message, &$received_order ) {
				$received_message = $message;
				$received_order   = $filtered_order;
				return '<strong>Use another payment method.</strong>';
			},
			10,
			2
		);

		$html = $this->render_thankyou_template( $order );

		$this->assertSame( self::CLASSIC_FAILED_MESSAGE, $received_message, 'The failure filter should receive the exact classic default.' );
		$this->assertSame( $order, $received_order, 'The failure filter should receive the exact order instance.' );
		$this->assertStringContainsString( '<strong>Use another payment method.</strong>', $html, 'The filtered message should render.' );
		$this->assertStringNotContainsString( self::CLASSIC_FAILED_MESSAGE, $html, 'The filtered message should replace the default.' );
	}

	/**
	 * @testdox An empty classic failure-filter result remains empty.
	 */
	public function test_empty_failed_message_filter_return_remains_empty(): void {
		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () {
				return '';
			}
		);

		$html = $this->render_thankyou_template( $this->create_failed_order() );

		$this->assertStringContainsString(
			'<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"></p>',
			$html,
			'The failed-message element should remain empty.'
		);
	}

	/**
	 * @testdox A non-string classic failure-filter result falls back to the default message.
	 */
	public function test_invalid_failed_message_filter_return_falls_back_to_default(): void {
		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () {
				return array( 'invalid' );
			}
		);

		$html = $this->render_thankyou_template( $this->create_failed_order() );

		$this->assertStringContainsString( self::CLASSIC_FAILED_MESSAGE, $html, 'An invalid filter return should restore the classic default.' );
	}

	/**
	 * @testdox The classic failed message keeps safe post HTML and removes unsafe markup.
	 */
	public function test_failed_message_preserves_safe_post_html_and_removes_unsafe_markup(): void {
		add_filter(
			'woocommerce_thankyou_order_failed_text',
			static function () {
				return '<strong>Retry</strong> or <a href="https://example.com/help" onclick="alert(1)">contact support</a><script>alert(1)</script>';
			}
		);

		$html = $this->render_thankyou_template( $this->create_failed_order() );

		$this->assertStringContainsString( '<strong>Retry</strong>', $html, 'Safe emphasis should remain.' );
		$this->assertStringContainsString( '<a href="https://example.com/help">contact support</a>', $html, 'A safe inline link should remain.' );
		$this->assertStringNotContainsString( 'onclick=', $html, 'Event handler attributes should be removed.' );
		$this->assertStringNotContainsString( '<script', $html, 'Script elements should be removed.' );
	}

	/**
	 * @testdox The classic failed template never invokes the legacy success-oriented message filter.
	 */
	public function test_failed_template_does_not_invoke_legacy_received_text_filter(): void {
		$legacy_filter_calls = 0;

		add_filter(
			'woocommerce_thankyou_order_received_text',
			static function () use ( &$legacy_filter_calls ) {
				++$legacy_filter_calls;
				return 'Unexpected success copy';
			},
			10,
			2
		);

		$this->render_thankyou_template( $this->create_failed_order() );

		$this->assertSame( 0, $legacy_filter_calls, 'The legacy filter should not run in the classic failed branch.' );
	}

	/**
	 * Create a failed order for a template test.
	 *
	 * @return WC_Order
	 */
	private function create_failed_order(): WC_Order {
		$order = new WC_Order();
		$order->set_status( OrderStatus::FAILED );
		$order->save();
		return $order;
	}

	/**
	 * Render the classic thankyou template.
	 *
	 * @param WC_Order $order Order to render.
	 * @return string
	 */
	private function render_thankyou_template( WC_Order $order ): string {
		return wc_get_template_html( 'checkout/thankyou.php', array( 'order' => $order ) );
	}
}
