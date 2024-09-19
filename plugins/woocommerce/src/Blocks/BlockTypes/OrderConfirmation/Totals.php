<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Totals class.
 */
class Totals extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-totals';

	/**
	 * This renders the content of the block within the wrapper.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
	 * @return string
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		if ( ! $permission ) {
			return $this->render_content_fallback();
		}
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, [ 'border_color', 'border_radius', 'border_width', 'border_style', 'background_color', 'text_color' ] );

		return $this->get_hook_content( 'woocommerce_order_details_before_order_table', [ $order ] ) . '
			<table cellspacing="0" class="wc-block-order-confirmation-totals__table ' . esc_attr( $classes_and_styles['classes'] ) . '" style="' . esc_attr( $classes_and_styles['styles'] ) . '">
				<thead>
					<tr>
						<th class="wc-block-order-confirmation-totals__product">' . esc_html__( 'Product', 'woocommerce' ) . '</th>
						<th class="wc-block-order-confirmation-totals__total">' . esc_html__( 'Total', 'woocommerce' ) . '</th>
					</tr>
				</thead>
				<tbody>
					' . $this->get_hook_content( 'woocommerce_order_details_before_order_table_items', [ $order ] ) . '
					' . $this->render_order_details_table_items( $order ) . '
					' . $this->get_hook_content( 'woocommerce_order_details_after_order_table_items', [ $order ] ) . '
				</tbody>
				<tfoot>
					' . $this->render_order_details_table_totals( $order ) . '
					</tfoot>
					</table>
			' . $this->render_order_details_customer_note( $order ) . '
			' . $this->get_hook_content( 'woocommerce_order_details_after_order_table', [ $order ] ) . '
			' . $this->get_hook_content( 'woocommerce_after_order_details', [ $order ] ) . '
		';
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 * @return string
	 */
	protected function get_inline_styles( array $attributes ) {
		$link_classes_and_styles       = StyleAttributesUtils::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributesUtils::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, [ 'border_color', 'border_radius', 'border_width', 'border_style' ] );

		return '
			.wc-block-order-confirmation-totals__table a {' . $link_classes_and_styles['style'] . '}
			.wc-block-order-confirmation-totals__table a:hover, .wc-block-order-confirmation-totals__table a:focus {' . $link_hover_classes_and_styles['style'] . '}
			.wc-block-order-confirmation-totals__table {' . $border_classes_and_styles['styles'] . '}
			.wc-block-order-confirmation-totals__table th, .wc-block-order-confirmation-totals__table td {' . $border_classes_and_styles['styles'] . '}
		';
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array     $attributes  Any attributes that currently are available from the block.
	 * @param string    $content    The block content.
	 * @param \WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		parent::enqueue_assets( $attributes, $content, $block );

		$styles = $this->get_inline_styles( $attributes );

		wp_add_inline_style( 'wc-blocks-style', $styles );
	}

	/**
	 * Render order details table items.
	 *
	 * Loosely based on the templates order-details.php and order-details-item.php from core.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	protected function render_order_details_table_items( $order ) {
		$return      = '';
		$order_items = array_filter(
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			$order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) ),
			function( $item ) {
                // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				return apply_filters( 'woocommerce_order_item_visible', true, $item );
			}
		);

		foreach ( $order_items as $item_id => $item ) {
			$product = $item->get_product();
			$return .= $this->render_order_details_table_item( $order, $item_id, $item, $product );
		}

		return $return;
	}

	/**
	 * Render an item in the order details table.
	 *
	 * @param \WC_Order         $order Order object.
	 * @param integer           $item_id Item ID.
	 * @param \WC_Order_Item    $item Item object.
	 * @param \WC_Product|false $product Product object if it exists.
	 * @return string
	 */
	protected function render_order_details_table_item( $order, $item_id, $item, $product ) {
		$is_visible = $product && $product->is_visible();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$row_class = apply_filters( 'woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$item_name    = apply_filters(
			'woocommerce_order_item_name',
			$product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $item->get_name() ) : $item->get_name(),
			$item,
			$is_visible
		);
		$qty          = $item->get_quantity();
		$refunded_qty = $order->get_qty_refunded_for_item( $item_id );
		$qty_display  = $refunded_qty ? '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>' : esc_html( $qty );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$item_qty = apply_filters(
			'woocommerce_order_item_quantity_html',
			'<strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $qty_display ) . '</strong>',
			$item
		);

		return '
			<tr class="' . esc_attr( $row_class ) . '">
				<td class="wc-block-order-confirmation-totals__product">
					' . wp_kses_post( $item_name ) . '&nbsp;
					' . wp_kses_post( $item_qty ) . '
					' . $this->get_hook_content( 'woocommerce_order_item_meta_start', [ $item_id, $item, $order, false ] ) . '
					' . wc_display_item_meta( $item, [ 'echo' => false ] ) . '
					' . $this->get_hook_content( 'woocommerce_order_item_meta_end', [ $item_id, $item, $order, false ] ) . '
					' . $this->render_order_details_table_item_purchase_note( $order, $product ) . '
				</td>
				<td class="wc-block-order-confirmation-totals__total">
					' . wp_kses_post( $order->get_formatted_line_subtotal( $item ) ) . '
				</td>
			</tr>
		';
	}

	/**
	 * Render an item purchase note.
	 *
	 * @param \WC_Order         $order Order object.
	 * @param \WC_Product|false $product Product object if it exists.
	 * @return string
	 */
	protected function render_order_details_table_item_purchase_note( $order, $product ) {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
		$purchase_note      = $product ? $product->get_purchase_note() : '';

		return $show_purchase_note && $purchase_note ? '<div class="product-purchase-note">' . wp_kses_post( $purchase_note ) . '</div>' : '';
	}

	/**
	 * Render order details table totals.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	protected function render_order_details_table_totals( $order ) {
		add_filter( 'woocommerce_order_shipping_to_display_shipped_via', '__return_empty_string' );

		$return     = '';
		$total_rows = array_diff_key(
			$order->get_order_item_totals(),
			array(
				'cart_subtotal'  => '',
				'payment_method' => '',
			)
		);

		foreach ( $total_rows as $total ) {
			$return .= '
				<tr>
					<th class="wc-block-order-confirmation-totals__label" scope="row">' . esc_html( $total['label'] ) . '</th>
					<td class="wc-block-order-confirmation-totals__total">' . wp_kses_post( $total['value'] ) . '</td>
				</tr>
			';
		}

		return $return;
	}

	/**
	 * Render customer note.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	protected function render_order_details_customer_note( $order ) {
		if ( ! $order->get_customer_note() ) {
			return '';
		}

		return '<div class="wc-block-order-confirmation-order-note">' .
					'<p class="wc-block-order-confirmation-order-note__label">' .
						esc_html__( 'Note:', 'woocommerce' ) .
					'</p>' .
					'<p>' . wp_kses( nl2br( wptexturize( $order->get_customer_note() ) ), [] ) . '</p>' .
				'</div>';
	}
}
