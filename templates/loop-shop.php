<?php

global $columns, $loop;

$loop = 0;

if (!isset($columns) || !$columns) $columns = apply_filters('loop_shop_columns', 4);

ob_start();

do_action('woocommerce_before_shop_loop');

if (have_posts()) : while (have_posts()) : the_post(); $_product = &new woocommerce_product( $post->ID ); if (!$_product->is_visible()) continue; $loop++;
	
	?>
	<li class="product <?php if ($loop%$columns==0) echo 'last'; if (($loop-1)%$columns==0) echo 'first'; ?>">
		
		<?php do_action('woocommerce_before_shop_loop_item'); ?>
		
		<a href="<?php the_permalink(); ?>">
			
			<?php do_action('woocommerce_before_shop_loop_item_title', $post, $_product); ?>
			
			<h3><?php the_title(); ?></h3>
			
			<?php do_action('woocommerce_after_shop_loop_item_title', $post, $_product); ?>
		
		</a>

		<?php do_action('woocommerce_after_shop_loop_item', $post, $_product); ?>
		
	</li><?php 
	
endwhile; endif;

if ($loop==0) :

	echo '<p class="info">'.__('No products found which match your selection.', 'woothemes').'</p>'; 
	
else :
	
	$found_posts = ob_get_clean();
	
	echo '<ul class="products">' . $found_posts . '</ul><div class="clear"></div>';
	
endif;

do_action('woocommerce_after_shop_loop');