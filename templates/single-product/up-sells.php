<?php
/**
 * Up-sells
 */

global $product;
$upsells = $product->get_upsells();
if (sizeof($upsells)==0) return;
?>
<div class="upsells products">
	<h2><?php _e('You may also like&hellip;', 'woocommerce') ?></h2>
	<?php
	$args = array(
		'post_type'	=> 'product',
		'ignore_sticky_posts'	=> 1,
		'posts_per_page' => 4,
		'no_found_rows' => 1,
		'orderby' => 'rand',
		'post__in' => $upsells
	);
	query_posts($args);
	woocommerce_get_template_part( 'loop', 'shop' );
	wp_reset_query();
	?>
</div>