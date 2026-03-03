<?php
/**
 * Admin View: Stock Notifications selected product
 *
 * @since    10.2.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$image              = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );
$image_src          = is_array( $image ) && isset( $image[0] ) ? $image[0] : '';
$stock_availability = $product->get_availability();
$identifier         = '#' . $product->get_id();
if ( ! empty( $product->get_sku() ) ) {
	$identifier = $product->get_sku();
}
?>

<img src="<?php echo esc_attr( $image_src ? $image_src : wc_placeholder_img_src() ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">

<div class="product-details">

	<p class="product-details__title">
		<?php echo esc_html( $product->get_name() ); ?>
		<span>
			<?php printf( '(%s)', esc_html( $identifier ) ); ?>
		</span>
		<a target="_blank" href="<?php echo esc_url( admin_url( sprintf( 'post.php?post=%d&action=edit', $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ) ) ); ?>"><span class="dashicons dashicons-external"></span></a>
	</p>

	<span class="product-details__price">
		<?php echo wp_kses_post( $product->get_price_html( 'edit' ) ); ?>
	</span>

	<span class="product-details__stock-status <?php echo esc_attr( $stock_availability['class'] ); ?>">
		<?php
		if ( empty( $stock_availability['availability'] ) && 'in-stock' === $stock_availability['class'] ) {
			echo esc_html__( 'In stock', 'woocommerce' );
		} else {
			echo esc_html( $stock_availability['availability'] );
		}
		?>
	</span>

</div>
