<?php
/**
 * Cross-sells
 */

global $woocommerce_loop, $woocommerce, $product;
$crosssells = $woocommerce->cart->get_cross_sells();
if (sizeof($crosssells)==0) return;
$woocommerce_loop['columns'] = 2;
?>
<div class="cross-sells">
	<h2><?php _e('You may be interested in&hellip;', 'woocommerce') ?></h2>
	<?php
	$args = array(
		'post_type'	=> 'product',
		'ignore_sticky_posts'	=> 1,
		'posts_per_page' => 2,
		'no_found_rows' => 1,
		'orderby' => 'rand',
		'post__in' => $crosssells
	);
	query_posts($args);
	woocommerce_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	?>
</div>