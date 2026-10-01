<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for the order item meta rows rendered inside the order items meta box.
 *
 * @package WooCommerce\Tests\Admin
 */
class Html_Order_Item_Meta_Test extends WC_Unit_Test_Case {

	/**
	 * Render the meta rows for an order item.
	 *
	 * @param WC_Order_Item $item The order item to render.
	 * @return string The rendered markup.
	 */
	private function render_item_meta( WC_Order_Item $item ): string {
		$item_id = $item->get_id();

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-item-meta.php';

		return (string) ob_get_clean();
	}

	/**
	 * Collect the meta fields the order form posts when the merchant clicks Update.
	 *
	 * @param string $markup Rendered meta rows.
	 * @param int    $item_id Order item ID.
	 * @return array<string, array<int, array<int, string>>>
	 */
	private function get_posted_meta_fields( string $markup, int $item_id ): array {
		$document = new DOMDocument();
		$document->loadHTML( '<?xml encoding="utf-8"?>' . $markup, LIBXML_NOERROR );
		$xpath  = new DOMXPath( $document );
		$fields = array(
			'order_item_id' => array( $item_id ),
			'meta_key'      => array(),
			'meta_value'    => array(),
		);

		foreach ( $xpath->query( '//input[starts-with(@name, "meta_key[")]' ) as $input ) {
			preg_match( '/\[(\d+)\]\[(\d+)\]/', $input->getAttribute( 'name' ), $ids );
			$fields['meta_key'][ (int) $ids[1] ][ (int) $ids[2] ] = $input->getAttribute( 'value' );
		}

		foreach ( $xpath->query( '//textarea[starts-with(@name, "meta_value[")]' ) as $textarea ) {
			preg_match( '/\[(\d+)\]\[(\d+)\]/', $textarea->getAttribute( 'name' ), $ids );
			$fields['meta_value'][ (int) $ids[1] ][ (int) $ids[2] ] = $textarea->textContent;
		}

		return $fields;
	}

	/**
	 * @testdox Updating an order in the admin keeps percent escapes in custom attribute values, so Order again restores the variation.
	 */
	public function test_admin_update_preserves_percent_escapes_for_order_again(): void {
		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';

		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$product   = new WC_Product_Variable();
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Finish' );
		$attribute->set_options( array( 'Black%20White', 'Gloss' ) );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'finish' => 'Black%20White' ) );
		$variation->set_regular_price( '10' );
		$variation->save();

		$order = wc_create_order( array( 'customer_id' => $user_id ) );
		$order->add_product( $variation, 1, array( 'variation' => array( 'finish' => 'Black%20White' ) ) );
		$order->save();

		$items  = $order->get_items();
		$item   = reset( $items );
		$markup = $this->render_item_meta( $item );

		$this->assertStringContainsString( 'Black White', $markup, 'The read-only view should show the decoded value.' );

		wc_save_order_items( $order->get_id(), $this->get_posted_meta_fields( $markup, $item->get_id() ) );

		$order = wc_get_order( $order->get_id() );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$items = $order->get_items();
		$this->assertSame( 'Black%20White', reset( $items )->get_meta( 'finish' ), 'Saving the admin form must not decode the stored value.' );

		$sut    = new WC_Cart_Session( WC()->cart );
		$method = new ReflectionMethod( WC_Cart_Session::class, 'populate_cart_from_order' );
		$method->setAccessible( true );
		$cart = $method->invoke( $sut, $order->get_id(), array() );

		$this->assertCount( 1, $cart, 'Order again should restore the ordered variation.' );
		$this->assertSame( 'Black%20White', reset( $cart )['variation']['attribute_finish'], 'Order again must keep the selected custom value.' );
	}
}
