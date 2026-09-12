<?php
declare( strict_types = 1 );

/**
 * Tests for the refund row rendered inside the order items meta box.
 *
 * @package WooCommerce\Tests\Admin
 */
class Html_Order_Refund_Test extends WC_Unit_Test_Case {

	/**
	 * Render the refund row markup for a refund.
	 *
	 * @param WC_Order_Refund $refund The refund to render.
	 * @return string The rendered markup.
	 */
	private function render_refund_row( WC_Order_Refund $refund ): string {
		// Both are read by the view and have no defaults of their own.
		$order_taxes     = array();
		$cogs_is_enabled = false;

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-refund.php';

		return (string) ob_get_clean();
	}

	/**
	 * Create a refund on a throwaway order, attributed to the given user.
	 *
	 * @param int $user_id The user to attribute the refund to.
	 * @return WC_Order_Refund
	 */
	private function create_refund_attributed_to( int $user_id ): WC_Order_Refund {
		$order = WC_Helper_Order::create_order();

		$refund = new WC_Order_Refund();
		$refund->set_amount( 10 );
		$refund->set_parent_id( $order->get_id() );
		$refund->set_refunded_by( $user_id );
		$refund->save();

		return $refund;
	}

	/**
	 * @testdox Should name the user who issued the refund.
	 */
	public function test_names_the_refunding_user(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'         => 'administrator',
				'display_name' => 'Ada Lovelace',
			)
		);

		$markup = $this->render_refund_row( $this->create_refund_attributed_to( $user_id ) );

		$this->assertStringContainsString( 'Ada Lovelace', $markup );
	}

	/**
	 * @testdox Should attribute a refund with no recorded user to the system.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/36329
	 */
	public function test_labels_an_unattributed_refund_as_system(): void {
		$markup = $this->render_refund_row( $this->create_refund_attributed_to( 0 ) );

		$this->assertStringContainsString( 'by System', $markup );
	}

	/**
	 * @testdox Should not attribute a refund to the system when its user was deleted.
	 */
	public function test_omits_attribution_when_the_refunding_user_was_deleted(): void {
		$user_id = self::factory()->user->create();
		$refund  = $this->create_refund_attributed_to( $user_id );
		wp_delete_user( $user_id );

		$markup = $this->render_refund_row( $refund );

		$this->assertStringNotContainsString(
			'by System',
			$markup,
			'A deleted user is not the system; the refund was issued by a person whose account is gone.'
		);
		$this->assertStringNotContainsString( ' by ', $markup );
	}
}
