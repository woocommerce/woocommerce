<?php
/**
 * Email Order Item
 *
 * Shows a line item inside the order emails table
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

global $woocommerce;

foreach ($items as $item) :

	// Get/prep product data
	$_product = $order->get_product_from_item( $item );
	$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
	$image = ($show_image) ? '<img src="'. current(wp_get_attachment_image_src( get_post_thumbnail_id( $_product->id ), 'thumbnail')) .'" alt="Product Image" height="'.$image_size[1].'" width="'.$image_size[0].'" style="vertical-align:middle; margin-right: 10px;" />' : '';

	?>
	<tr>
		<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php

			// Show title/image etc
			echo 	apply_filters( 'woocommerce_order_product_image', $image, $_product, $show_image);

			// Product name
			echo 	apply_filters( 'woocommerce_order_product_title', $item['name'], $_product );

			// SKU
			echo 	($show_sku && $_product->get_sku()) ? ' (#' . $_product->get_sku() . ')' : '';

			// File URL
			echo 	( $show_download_links && $_product->exists() && $_product->is_downloadable() && $_product->has_file() ) ? '<br/><small>' . __( 'Download:', 'woocommerce' ) . ' <a href="' . $order->get_downloadable_file_url( $item['id'], $item['variation_id'] ) . '" target="_blank">' . $order->get_downloadable_file_url( $item['id'], $item['variation_id'] ) . '</a></small>' : '';

			// Variation
			echo 	($item_meta->meta) ? '<br/><small>' . nl2br( $item_meta->display( true, true ) ) . '</small>' : '';

		?></td>
		<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php echo $item['qty'] ;?></td>
		<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td>
	</tr>

	<?php if ($show_purchase_note && $purchase_note = get_post_meta( $_product->id, '_purchase_note', true)) : ?>
		<tr>
			<td colspan="3" style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php echo apply_filters('the_content', $purchase_note); ?></td>
		</tr>
	<?php endif; ?>

<?php endforeach; ?>