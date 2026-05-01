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

$shop_url = wc_get_page_permalink( 'shop' );
?>
<div class="woocommerce-review-order woocommerce-review-order--empty">
	<div class="woocommerce-review-order__empty-card">
		<h1 class="woocommerce-review-order__empty-title">
			<?php esc_html_e( 'Thanks for your reviews!', 'woocommerce' ); ?>
		</h1>

		<p class="woocommerce-review-order__empty-body">
			<?php esc_html_e( 'You have nothing left to review on this order. Your feedback helps other shoppers make better decisions.', 'woocommerce' ); ?>
		</p>

		<?php if ( $reviewed_count > 0 ) : ?>
			<p class="woocommerce-review-order__empty-summary">
				<?php
				$rating_text = '';
				if ( $average_rating > 0 ) {
					$rating_text = sprintf(
						/* translators: %s: average rating with one decimal, e.g. "4.5" */
						__( 'average rating %s out of 5', 'woocommerce' ),
						number_format_i18n( $average_rating, 1 )
					);
				}

				$rating_suffix = '' === $rating_text ? '' : ' (' . $rating_text . ')';

				/* translators: 1: number of reviews 2: average-rating phrase, optional */
				$summary_template = _n(
					'You left %1$d review on this order%2$s.',
					'You left %1$d reviews on this order%2$s.',
					(int) $reviewed_count,
					'woocommerce'
				);

				printf(
					esc_html( $summary_template ),
					(int) $reviewed_count,
					esc_html( $rating_suffix )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( $shop_url ) : ?>
			<p class="woocommerce-review-order__empty-actions">
				<a class="button" href="<?php echo esc_url( $shop_url ); ?>">
					<?php esc_html_e( 'Continue shopping', 'woocommerce' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</div>
