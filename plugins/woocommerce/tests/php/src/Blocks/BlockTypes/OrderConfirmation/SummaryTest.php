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
	 * @testdox The billing email reads as entered after wptexturize() runs over the block output.
	 */
	public function test_billing_email_survives_wptexturize(): void {
		$order = wc_create_order();
		$order->set_billing_email( "o'neil--shop@48x17.com" );
		$order->save();

		$text = html_entity_decode( wp_strip_all_tags( wptexturize( $this->render( $order ) ) ), ENT_QUOTES | ENT_HTML5 );

		$this->assertStringContainsString( "o'neil--shop@48x17.com", $text, 'The email should not gain a curly apostrophe, en dash or multiplication sign.' );
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
