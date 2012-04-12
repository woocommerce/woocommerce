<?php
/**
 * Related Products
 */

global $product, $woocommerce_loop;

$related = $product->get_related(); 

if ( sizeof($related) == 0 ) return;
?>
<div class="related products"><h2><?php _e('Related Products', 'woocommerce'); ?></h2>
	<?php
		$args = array(
			'post_type'				=> 'product',
			'ignore_sticky_posts'	=> 1,
			'no_found_rows' 		=> 1,
			'posts_per_page' 		=> $posts_per_page,
			'orderby' 				=> $orderby,
			'post__in' 				=> $related
		);
		$args = apply_filters('woocommerce_related_products_args', $args);
		
		query_posts($args);
		
		$woocommerce_loop['columns'] = $columns;

		woocommerce_get_template_part( 'loop', 'shop' );
		
		wp_reset_query();
	?>
</div>