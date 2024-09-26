<?php
/**
 * Show a grid of thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/brands/widgets/brand-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

declare( strict_types = 1);

$wrapper_class = 'fluid-columns';
if ( ! $fluid_columns && in_array( $columns, array( 1, 2, 3, 4, 5, 6 ), true ) ) {
	$wrapper_class = 'columns-' . $columns;
}
?>
<ul class="brand-thumbnails <?php echo esc_attr( $wrapper_class ); ?>">

<?php
foreach ( array_values( $brands ) as $index => $brand ) :
	$class = '';
	if ( 0 === $index || 0 === $index % $columns ) {
		$class = 'first';
	} elseif ( 0 === ( $index + 1 ) % $columns ) {
		$class = 'last';
	}
	?>

	<li class="<?php echo esc_attr( $class ); ?>">
		<a href="<?php echo esc_url( get_term_link( $brand->slug, 'product_brand' ) ); ?>" title="<?php echo esc_attr( $brand->name ); ?>">
			<?php echo wc_get_brand_thumbnail_image( $brand ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>
	</li>

<?php endforeach; ?>

</ul>
