<?php
declare( strict_types = 1 );

/**
 * Tests for the order actions meta box.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Order_Actions_Test
 */
class WC_Meta_Box_Order_Actions_Test extends WC_Unit_Test_Case {

	/**
	 * The order actions box should keep the legacy trash link in its historical
	 * wrapper instead of moving it into a new menu surface.
	 */
	public function test_output_keeps_trash_link_in_legacy_delete_action_wrapper() {
		wp_set_current_user(
			self::factory()->user->create(
				array(
					'role' => 'administrator',
				)
			)
		);

		$order       = WC_Helper_Order::create_order();
		$menu_filter = function ( $menu_items ) {
			$menu_items['test_item'] = array(
				'label' => 'Experimental menu item',
				'url'   => 'https://example.com/',
			);

			return $menu_items;
		};
		$delete_cap_filter = function ( $caps, $cap, $user_id, $args ) use ( $order ) {
			if ( 'delete_post' === $cap && isset( $args[0] ) && $order->get_id() === (int) $args[0] ) {
				return array( 'exist' );
			}

			return $caps;
		};

		add_filter( 'woocommerce_order_actions_menu_items', $menu_filter );
		add_filter( 'map_meta_cap', $delete_cap_filter, 10, 4 );
		try {
			$output = $this->render_order_actions_meta_box( $order );
		} finally {
			remove_filter( 'woocommerce_order_actions_menu_items', $menu_filter );
			remove_filter( 'map_meta_cap', $delete_cap_filter, 10 );
		}

		$expected_delete_text = EMPTY_TRASH_DAYS ? 'Move to trash' : 'Delete permanently';

		$this->assertStringContainsString( 'id="delete-action"', $output );
		$this->assertStringContainsString( 'class="submitdelete deletion"', $output );
		$this->assertStringContainsString( $expected_delete_text, $output );
		$this->assertStringNotContainsString( 'Move to Trash', $output );
		$this->assertStringNotContainsString( 'wc-order-actions-menu', $output );
		$this->assertStringNotContainsString( 'role="menu"', $output );
		$this->assertStringNotContainsString( 'Experimental menu item', $output );
	}

	/**
	 * Render the order actions meta box and return its HTML.
	 *
	 * @param WC_Order $order Order object.
	 * @return string Rendered markup.
	 */
	private function render_order_actions_meta_box( WC_Order $order ): string {
		ob_start();
		WC_Meta_Box_Order_Actions::output( $order );
		return ob_get_clean();
	}
}
