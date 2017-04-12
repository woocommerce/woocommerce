<table class="order_items" cellspacing="0">
	<?php foreach ( $items as $item ):
		$product        = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
		$item_meta      = new WC_Order_Item_Meta( $item, $product );
		$item_meta_html = $item_meta->display( true, true );
	?>
		<tr class="<?php echo apply_filters( 'woocommerce_admin_order_item_class', '', $item, $the_order ); ?>">
			<td class="qty"><?php echo esc_html( $item->get_quantity() ); ?></td>
			<td class="name">
				<?php if ( $product ): ?>
					<?php echo ( wc_product_sku_enabled() && $product->get_sku() ) ? $product->get_sku() . ' - ' : ''; ?><a href="<?php echo get_edit_post_link( $product->get_id() ); ?>"><?php echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ); ?></a>
				<?php else: ?>
					<?php echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ); ?>
				<?php endif;?>

				<?php if ( ! empty( $item_meta_html ) ): ?>
					<?php echo wc_help_tip( $item_meta_html ); ?>
				<?php endif;?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>