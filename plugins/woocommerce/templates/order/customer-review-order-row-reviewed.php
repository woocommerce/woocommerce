<?php
/**
 * Customer Review Order — locked "Reviewed" row.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/customer-review-order-row-reviewed.php`.
 *
 * Rendered for line items the customer has already reviewed (matched by
 * billing email). The form row is replaced with a static summary of the
 * existing review.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var WC_Order_Item_Product $item    Order line item.
 * @var WC_Product            $product Product attached to the line item.
 * @var WC_Order              $order   Order being reviewed.
 * @var WP_Comment            $review  The existing review comment.
 */

defined( 'ABSPATH' ) || exit;

if ( ! $item instanceof WC_Order_Item_Product || ! $product instanceof WC_Product || ! $review instanceof WP_Comment ) {
	return;
}

$product_link = $product->is_visible() ? get_permalink( $product->get_id() ) : '';
$product_name = $item->get_name();
$image_html   = $product->get_image( 'woocommerce_thumbnail' );

$rating = (int) get_comment_meta( (int) $review->comment_ID, 'rating', true );
if ( $rating < 1 || $rating > 5 ) {
	$rating = 0;
}

$review_text = (string) $review->comment_content;
$summary     = wp_trim_words( $review_text, 30, '…' );

$posted_at_ts   = strtotime( $review->comment_date_gmt . ' UTC' );
$posted_at_text = $posted_at_ts ? (string) wp_date( get_option( 'date_format' ), $posted_at_ts ) : '';
$posted_label   = sprintf(
	/* translators: %s: human-readable date posted, e.g. "March 5, 2026" */
	esc_html__( 'Reviewed on %s', 'woocommerce' ),
	esc_html( $posted_at_text )
);

$labels = \Automattic\WooCommerce\Internal\OrderReviews\StarRating::get_labels();
?>
<li class="woocommerce-review-order__item woocommerce-review-order__item--reviewed">
	<p class="woocommerce-review-order__item-title">
		<?php if ( $product_link ) : ?>
			<a href="<?php echo esc_url( $product_link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $product_name ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $product_name ); ?>
		<?php endif; ?>
	</p>

	<div class="woocommerce-review-order__item-row">
		<div class="woocommerce-review-order__item-image">
			<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_image() returns escaped HTML. ?>
		</div>

		<div class="woocommerce-review-order__item-fields">
			<div class="woocommerce-review-order__item-reviewed-header">
				<span class="woocommerce-review-order__item-reviewed-badge">
					<?php esc_html_e( 'Reviewed', 'woocommerce' ); ?>
				</span>
				<span class="woocommerce-review-order__item-reviewed-date">
					<?php echo $posted_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped via esc_html() inside sprintf(). ?>
				</span>
			</div>

			<?php
			if ( $rating > 0 ) :
				/* translators: 1: numeric rating 2: rating label, e.g. "Good" */
				$rating_aria   = sprintf( __( '%1$d out of 5 stars: %2$s', 'woocommerce' ), $rating, $labels[ $rating ] ?? '' );
				$rating_glyphs = '';
				for ( $i = 1; $i <= 5; $i++ ) {
					$rating_glyphs .= $i <= $rating ? '★' : '☆';
				}
				?>
				<p class="woocommerce-review-order__item-reviewed-rating" aria-label="<?php echo esc_attr( $rating_aria ); ?>">
					<span aria-hidden="true"><?php echo esc_html( $rating_glyphs ); ?></span>
					<span class="woocommerce-review-order__item-reviewed-label">
						<?php echo isset( $labels[ $rating ] ) ? esc_html( $labels[ $rating ] ) : ''; ?>
					</span>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $summary ) : ?>
				<blockquote class="woocommerce-review-order__item-reviewed-text">
					<?php echo esc_html( $summary ); ?>
				</blockquote>
			<?php endif; ?>

			<?php if ( $product_link ) : ?>
				<p class="woocommerce-review-order__item-reviewed-link">
					<a href="<?php echo esc_url( $product_link ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'View on product page', 'woocommerce' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</div>
</li>
