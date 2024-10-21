<?php
/**
 * Brand A-Z listing
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/brands/shortcodes/brands-a-z.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @usedby [product_brand_list]
 * @version 9.4.0
 */

declare( strict_types = 1);
?>
<div id="brands_a_z">

	<ul class="brands_index">
		<?php
		foreach ( $index as $i ) {
			if ( isset( $product_brands[ $i ] ) ) {
				echo '<li><a href="#brands-' . esc_attr( $i ) . '">' . esc_html( $i ) . '</a></li>';
			} elseif ( $show_empty ) {
				echo '<li><span>' . esc_html( $i ) . '</span></li>';
			}
		}
		?>
	</ul>

	<?php
	foreach ( $index as $i ) {
		if ( isset( $product_brands[ $i ] ) ) {
			?>

			<h3 id="brands-<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></h3>

			<ul class="brands">
				<?php
				foreach ( $product_brands[ $i ] as $brand ) {
					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( get_term_link( $brand->slug, 'product_brand' ) ),
						esc_html( $brand->name )
					);
				}
				?>
			</ul>

			<?php if ( $show_top_links ) { ?>
				<a class="top" href="#brands_a_z"><?php esc_html_e( '&uarr; Top', 'woocommerce' ); ?></a>
			<?php } ?>

			<?php
		}
	}
	?>

</div>
