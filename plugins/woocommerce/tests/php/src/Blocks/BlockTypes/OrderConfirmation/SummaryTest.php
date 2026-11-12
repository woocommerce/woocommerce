<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Summary as SummaryBlock;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the Order Confirmation Summary block.
 */
class SummaryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox The billing email renders as an escaped address element.
	 */
	public function test_billing_email_renders_in_address(): void {
		$order = wc_create_order();
		$order->set_billing_email( "o'neil--shop@48x17.com" );
		$order->save();

		$html = $this->render( $order );

		$this->assertStringContainsString(
			'<address class="wc-block-order-confirmation-summary-list-item__value">o&#039;neil--shop@48x17.com</address>',
			$html,
			'The email value should be an escaped address element, which wptexturize() skips on order pages.'
		);
	}

	/**
	 * @testdox Without a billing email, the block renders no email row.
	 */
	public function test_no_email_row_without_email(): void {
		$order = wc_create_order();
		$order->set_billing_email( '' );
		$order->save();

		$this->assertStringNotContainsString( 'Email:', $this->render( $order ), 'The email row should not render.' );
	}

	/**
	 * Render the block content with full view permission.
	 *
	 * @param WC_Order $order Order to render.
	 * @return string
	 */
	private function render( WC_Order $order ): string {
		$sut = new class() extends SummaryBlock {
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
