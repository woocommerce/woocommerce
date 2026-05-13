<?php
/**
 * Customer Review Order — empty-state thank-you view.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/customer-review-order-empty.php`.
 *
 * Rendered when every eligible line item on the order is either already
 * reviewed by the customer or skipped (reviews disabled on the product),
 * so there is nothing left to do on the form.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var WC_Order $order            Order being reviewed.
 * @var int      $reviewed_count   Number of reviews this customer left on this order.
 * @var float    $average_rating   Average rating across those reviews (0.0 if none).
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order instanceof WC_Order ) {
	return;
}

// Fall back to the site home if the shop page is missing, mirroring how
// `Endpoint::gate_request()` handles a missing host page.
$shop_url = wc_get_page_permalink( 'shop' );
$cta_url  = $shop_url ? $shop_url : home_url( '/' );
?>
<div class="woocommerce-review-order woocommerce-review-order--empty">
	<div class="woocommerce-review-order__empty-card">
		<h1 class="woocommerce-review-order__empty-title">
			<?php
			if ( $reviewed_count > 0 ) {
				esc_html_e( 'Thanks for your reviews!', 'woocommerce' );
			} else {
				esc_html_e( 'Nothing to review here', 'woocommerce' );
			}
			?>
		</h1>

		<p class="woocommerce-review-order__empty-body">
			<?php
			if ( $reviewed_count > 0 ) {
				esc_html_e( 'You have nothing left to review on this order. Your feedback helps other shoppers make better decisions.', 'woocommerce' );
			} else {
				esc_html_e( 'There are no products on this order that are open for reviews right now.', 'woocommerce' );
			}
			?>
		</p>

		<?php if ( $reviewed_count > 0 ) : ?>
			<p class="woocommerce-review-order__empty-summary">
				<?php
				if ( $average_rating > 0 ) {
					$avg = number_format_i18n( $average_rating, 1 );
					/* translators: 1: number of reviews left, 2: average rating with one decimal, e.g. "4.5" */
					$summary_template = _n(
						'You left %1$d review on this order (average rating %2$s out of 5).',
						'You left %1$d reviews on this order (average rating %2$s out of 5).',
						(int) $reviewed_count,
						'woocommerce'
					);
					printf(
						esc_html( $summary_template ),
						(int) $reviewed_count,
						esc_html( $avg )
					);
				} else {
					/* translators: %d: number of reviews left */
					$summary_template = _n(
						'You left %d review on this order.',
						'You left %d reviews on this order.',
						(int) $reviewed_count,
						'woocommerce'
					);
					printf(
						esc_html( $summary_template ),
						(int) $reviewed_count
					);
				}//end if
				?>
			</p>
		<?php endif; ?>

		<p class="woocommerce-review-order__empty-actions">
			<a class="button" href="<?php echo esc_url( $cta_url ); ?>">
				<?php esc_html_e( 'Continue shopping', 'woocommerce' ); ?>
			</a>
		</p>
	</div>
</div>
