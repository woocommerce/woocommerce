<?php
/**
 * Customer Review Order — per-item form row.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/customer-review-order-row.php`.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var WC_Order_Item_Product $item            Order line item being rendered.
 * @var WC_Product            $product         Product attached to the line item.
 * @var WC_Order              $order           Order being reviewed.
 * @var int                   $row_index       Zero-based row index, used in input names.
 * @var int                   $existing_rating Pre-fill rating (0 when no prior review for this order).
 * @var string                $existing_text   Pre-fill review text (empty when no prior review for this order).
 */

defined( 'ABSPATH' ) || exit;

if ( ! $item instanceof WC_Order_Item_Product || ! $product instanceof WC_Product || ! $order instanceof WC_Order ) {
	return;
}

$existing_rating = isset( $existing_rating ) ? (int) $existing_rating : 0;
$existing_text   = isset( $existing_text ) ? (string) $existing_text : '';

$item_id         = $item->get_id();
$product_id      = $product->get_id();
$product_link    = $product->is_visible() ? get_permalink( $product_id ) : '';
$product_name    = $item->get_name();
$image_html      = $product->get_image( 'woocommerce_thumbnail' );
$rating_label_id = 'woocommerce-review-rating-label-' . $item_id;
$review_label_id = 'woocommerce-review-text-label-' . $item_id;
$review_input_id = 'woocommerce-review-text-' . $item_id;

$rating_control = \Automattic\WooCommerce\Internal\OrderReviews\StarRating::render(
	array(
		'name'      => 'reviews[' . $row_index . '][rating]',
		'id_prefix' => 'woocommerce-review-rating-' . $item_id,
		'label_id'  => $rating_label_id,
		'selected'  => $existing_rating,
	)
);
?>
<li
	class="woocommerce-review-order__item"
	data-row-index="<?php echo esc_attr( (string) $row_index ); ?>"
	data-initial-rating="<?php echo esc_attr( (string) $existing_rating ); ?>"
	data-initial-text="<?php echo esc_attr( $existing_text ); ?>"
>
	<p class="woocommerce-review-order__item-title">
		<?php if ( $product_link ) : ?>
			<a href="<?php echo esc_url( $product_link ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $product_name ); ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'woocommerce' ); ?></span>
			</a>
		<?php else : ?>
			<?php echo esc_html( $product_name ); ?>
		<?php endif; ?>
	</p>

	<div class="woocommerce-review-order__item-row">
		<div class="woocommerce-review-order__item-image">
			<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_image() returns escaped HTML. ?>
		</div>

		<div class="woocommerce-review-order__item-rating">
			<input type="hidden" name="reviews[<?php echo esc_attr( (string) $row_index ); ?>][product_id]" value="<?php echo esc_attr( (string) $product_id ); ?>" />
			<input type="hidden" name="reviews[<?php echo esc_attr( (string) $row_index ); ?>][order_item_id]" value="<?php echo esc_attr( (string) $item_id ); ?>" />

			<p id="<?php echo esc_attr( $rating_label_id ); ?>" class="woocommerce-review-order__item-rating-label">
				<?php
				printf(
					'%1$s <span class="required" aria-hidden="true">*</span><span class="screen-reader-text"> %2$s</span>',
					esc_html__( 'Your rating', 'woocommerce' ),
					esc_html__( 'Required', 'woocommerce' )
				);
				?>
			</p>
			<?php echo $rating_control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- StarRating::render() returns escaped HTML. ?>
		</div>

		<div class="woocommerce-review-order__item-review">
			<label id="<?php echo esc_attr( $review_label_id ); ?>" for="<?php echo esc_attr( $review_input_id ); ?>" class="woocommerce-review-order__item-review-label">
				<?php esc_html_e( 'Your review', 'woocommerce' ); ?>
			</label>
			<textarea
				id="<?php echo esc_attr( $review_input_id ); ?>"
				class="woocommerce-review-order__item-review-textarea"
				name="reviews[<?php echo esc_attr( (string) $row_index ); ?>][text]"
				rows="3"
				placeholder="<?php esc_attr_e( 'Share your experience with this product...', 'woocommerce' ); ?>"
			><?php echo esc_textarea( $existing_text ); ?></textarea>
		</div>
	</div>

	<?php
	/**
	 * Fires after the rating + textarea inside a Review Order form row, as a
	 * sibling of the row's columns so injected fields render below them.
	 *
	 * Echo HTML directly; the surrounding container expects no return value.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Order_Item_Product $item       The line item being reviewed.
	 * @param WC_Product            $product    The associated product.
	 * @param WC_Order              $order      The order.
	 * @param int                   $row_index  Zero-based row index for input names.
	 */
	do_action( 'woocommerce_review_order_form_fields', $item, $product, $order, $row_index );
	?>
</li>
