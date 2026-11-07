<?php
/**
 * Order fulfillment progress
 *
 * Shows the customer where their order is: Order received, Preparing, then Ready for pickup or Shipped,
 * then Picked up or Delivered.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-fulfillment-progress.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.3.0
 *
 * @var WC_Order $order The order.
 * @var array    $steps The steps, each with `key`, `label` and `state` ( done, current, upcoming ).
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $steps ) ) {
	return;
}

$current_label = '';
foreach ( $steps as $step ) {
	if ( 'current' === $step['state'] ) {
		$current_label = $step['label'];
	}
}
?>
<section class="woocommerce-fulfillment-progress" aria-label="<?php esc_attr_e( 'Order progress', 'woocommerce' ); ?>">
	<ol class="woocommerce-fulfillment-progress__steps">
		<?php foreach ( $steps as $index => $step ) : ?>
			<li class="woocommerce-fulfillment-progress__step woocommerce-fulfillment-progress__step--<?php echo esc_attr( $step['state'] ); ?> woocommerce-fulfillment-progress__step--<?php echo esc_attr( $step['key'] ); ?>"<?php echo 'current' === $step['state'] ? ' aria-current="step"' : ''; ?>>
				<span class="woocommerce-fulfillment-progress__marker" aria-hidden="true"><?php echo 'done' === $step['state'] ? '&#10003;' : esc_html( (string) ( $index + 1 ) ); ?></span>
				<span class="woocommerce-fulfillment-progress__label"><?php echo esc_html( $step['label'] ); ?></span>
				<?php if ( 'done' === $step['state'] ) : ?>
					<span class="screen-reader-text"><?php esc_html_e( '(done)', 'woocommerce' ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php if ( '' !== $current_label ) : ?>
		<p class="woocommerce-fulfillment-progress__summary">
			<?php
			/* translators: %s: the current step, e.g. Preparing */
			printf( esc_html__( 'Current status: %s', 'woocommerce' ), '<strong>' . esc_html( $current_label ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			?>
		</p>
	<?php endif; ?>
</section>
