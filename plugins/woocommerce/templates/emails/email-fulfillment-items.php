<?php
/**
 * Email Fulfillment Items
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-fulfillment-items.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.1.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$margin_side = is_rtl() ? 'left' : 'right';

$price_text_align = 'right';

foreach ( $items as $item_id => $item ) :
	$product       = $item->item->get_product();
	$sku           = '';
	$purchase_note = '';
	$image         = '';

	/**
	 * Email Order Item Visibility hook.
	 *
	 * This filter allows you to control the visibility of order items in emails.
	 *
	 * @param bool $visible Whether the item is visible in the email.
	 * @param WC_Order_Item_Product $item The order item object.
	 * @since 2.1.0
	 */
	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item->item ) ) {
		continue;
	}

	if ( is_object( $product ) ) {
		$sku           = $product->get_sku();
		$purchase_note = $product->get_purchase_note();
		$image         = $product->get_image( $image_size );
	}

	/**
	 * Email Order Item Thumbnail hook.
	 *
	 * @since 2.1.0
	 */
	$order_item_class = apply_filters( 'woocommerce_order_item_class', 'order_item', $item->item, $order );
	?>
	<tr class="<?php echo esc_attr( $order_item_class ); ?>">
		<td class="td font-family text-align-left" style="vertical-align: middle; word-wrap:break-word;">
			<table class="order-item-data">
				<tr>
					<?php
					// Show title/image etc.
					if ( $show_image ) {
						/**
						 * Email Order Item Thumbnail hook.
						 *
						 * @param string                $image The image HTML.
						 * @param WC_Order_Item_Product $item  The item being displayed.
						 * @since 2.1.0
						 */
						echo '<td>' . wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $item->item ) ) . '</td>';
					}
					?>
					<td>
						<?php
						/**
						 * Order Item Name hook.
						 *
						 * @param string                $item_name The item name HTML.
						 * @param WC_Order_Item_Product $item      The item being displayed.
						 * @since 2.1.0
						 */
						echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->item->get_name(), $item->item, false ) );

						// SKU.
						if ( $show_sku && $sku ) {
							echo wp_kses_post( ' (#' . $sku . ')' );
						}

						/**
						 * Allow other plugins to add additional product information.
						 *
						 * @param int                   $item_id    The item ID.
						 * @param WC_Order_Item_Product $item       The item object.
						 * @param WC_Order              $order      The order object.
						 * @param bool                  $plain_text Whether the email is plain text or not.
						 * @since 2.3.0
						 */
						do_action( 'woocommerce_order_item_meta_start', $item_id, $item->item, $order, $plain_text );

						$item_meta = wc_display_item_meta(
							$item->item,
							array(
								'before'       => '',
								'after'        => '',
								'separator'    => '<br>',
								'echo'         => false,
								'label_before' => '<span>',
								'label_after'  => ':</span> ',
							)
						);
						echo '<div class="email-order-item-meta">';
						// Using wp_kses instead of wp_kses_post to remove all block elements.
						echo wp_kses(
							$item_meta,
							array(
								'br'   => array(),
								'span' => array(),
								'a'    => array(
									'href'   => true,
									'target' => true,
									'rel'    => true,
									'title'  => true,
								),
							)
						);
						echo '</div>';

						/**
						 * Allow other plugins to add additional product information.
						 *
						 * @param int                   $item_id    The item ID.
						 * @param WC_Order_Item_Product $item       The item object.
						 * @param WC_Order              $order      The order object.
						 * @param bool                  $plain_text Whether the email is plain text or not.
						 * @since 2.3.0
						 */
						do_action( 'woocommerce_order_item_meta_end', $item_id, $item->item, $order, $plain_text );

						?>
					</td>
				</tr>
			</table>
		</td>
		<td class="td font-family text-align-<?php echo esc_attr( $price_text_align ); ?>" style="vertical-align:middle;">
			<?php
			echo '&times;';
			$qty         = $item->qty;
			$qty_display = esc_html( $qty );
			/**
			 * Email Order Item Quantity hook.
			 *
			 * @since 2.4.0
			 */
			echo wp_kses_post( apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item->item ) );
			?>
		</td>
		<td class="td font-family text-align-<?php echo esc_attr( $price_text_align ); ?>" style="vertical-align:middle;">
			<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item->item ) ); ?>
		</td>
	</tr>
	<?php

	if ( $show_purchase_note && $purchase_note ) {
		?>
		<tr>
			<td colspan="3" class="font-family text-align-left" style="vertical-align:middle;">
				<?php
				echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) );
				?>
			</td>
		</tr>
		<?php
	}
	?>

<?php endforeach; ?>
