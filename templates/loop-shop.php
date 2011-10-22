<?php

global $woocommerce_loop;

$woocommerce_loop['loop'] = 0;
$woocommerce_loop['show_products'] = true;

if (!isset($woocommerce_loop['columns']) || !$woocommerce_loop['columns']) $woocommerce_loop['columns'] = apply_filters('loop_shop_columns', 4);

ob_start();

do_action('woocommerce_before_shop_loop');

if ($woocommerce_loop['show_products'] && have_posts()) : while (have_posts()) : the_post(); $_product = &new woocommerce_product( $post->ID ); if (!$_product->is_visible()) continue; $woocommerce_loop['loop']++;
	
	?>
	<li class="product <?php if ($woocommerce_loop['loop']%$woocommerce_loop['columns']==0) echo 'last'; if (($woocommerce_loop['loop']-1)%$woocommerce_loop['columns']==0) echo 'first'; ?>">
		
		<?php do_action('woocommerce_before_shop_loop_item'); ?>
		
		<a href="<?php the_permalink(); ?>">
			
			<?php do_action('woocommerce_before_shop_loop_item_title', $post, $_product); ?>
			
			<h3><?php the_title(); ?></h3>
			
			<?php do_action('woocommerce_after_shop_loop_item_title', $post, $_product); ?>
		
		</a>

		<?php do_action('woocommerce_after_shop_loop_item', $post, $_product); ?>
		
	</li><?php 
	
endwhile; endif;

if ($woocommerce_loop['loop']==0) :

	echo '<p class="info">'.__('No products found which match your selection.', 'woothemes').'</p>'; 
	
else :
	
	$found_posts = ob_get_clean();
	
	echo '<ul class="products">' . $found_posts . '</ul><div class="clear"></div>';
	
endif;

do_action('woocommerce_after_shop_loop');