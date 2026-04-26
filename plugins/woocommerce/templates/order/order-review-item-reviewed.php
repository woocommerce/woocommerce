<?php
/**
 * Order Review Item — Already Reviewed (locked row)
 *
 * Renders a read-only "Reviewed" row for a product that the customer has
 * already reviewed. Displays the stored star rating, a truncated excerpt
 * of the review body, a "Reviewed" badge, and an optional link to the
 * product page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-review-item-reviewed.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var int         $product_id   Product ID.
 * @var string      $product_name Product name.
 * @var string      $product_url  Product permalink (may be empty).
 * @var int         $rating       Stored star rating (1–5).
 * @var string      $review_body  Full review text.
 * @var WP_Comment  $review       The review comment object.
 * @var WC_Product|null $product  Product object (may be null if deleted).
 * @var WC_Order    $order        Order object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$max_excerpt_length = 120;
$excerpt            = wp_strip_all_tags( $review_body );
$is_truncated       = mb_strlen( $excerpt ) > $max_excerpt_length;
$excerpt_display    = $is_truncated ? mb_substr( $excerpt, 0, $max_excerpt_length ) . '&hellip;' : $excerpt;
?>

<tr class="wc-order-review-item wc-order-review-item--reviewed">
	<td class="wc-order-review-item__product">
		<span class="wc-order-review-item__name">
			<?php if ( $product_url ) : ?>
				<a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $product_name ); ?>
			<?php endif; ?>
		</span>
	</td>

	<td class="wc-order-review-item__rating">
		<?php if ( $rating > 0 ) : ?>
			<div class="wc-order-review-item__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %d out of 5', 'woocommerce' ), $rating ) ); ?>">
				<?php echo wc_get_rating_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</td>

	<td class="wc-order-review-item__body">
		<?php if ( $excerpt_display ) : ?>
			<p class="wc-order-review-item__excerpt"><?php echo esc_html( $excerpt_display ); ?></p>
		<?php endif; ?>
	</td>

	<td class="wc-order-review-item__status">
		<span class="wc-order-review-item__badge wc-order-review-item__badge--reviewed">
			<?php esc_html_e( 'Reviewed', 'woocommerce' ); ?>
		</span>

		<?php if ( $product_url ) : ?>
			<a href="<?php echo esc_url( $product_url . '#reviews' ); ?>" class="wc-order-review-item__view-link">
				<?php esc_html_e( 'View on product page', 'woocommerce' ); ?>
			</a>
		<?php endif; ?>
	</td>
</tr>
