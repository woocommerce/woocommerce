<?php get_header('shop'); ?>
	  
<?php do_action('woocommerce_before_main_content'); // <div id="container"><div id="content" role="main"> ?>

	<?php 
		$shop_page_id = get_option('woocommerce_shop_page_id');
		$shop_page = get_post($shop_page_id);
		$shop_page_title = (get_option('woocommerce_shop_page_title')) ? get_option('woocommerce_shop_page_title') : $shop_page->post_title;
	?>
	
	<?php if (is_search()) : ?>		
		<h1 class="page-title"><?php _e('Search Results:', 'woothemes'); ?> &ldquo;<?php the_search_query(); ?>&rdquo; <?php if (get_query_var('paged')) echo ' &mdash; Page '.get_query_var('paged'); ?></h1>
	<?php else : ?>
		<h1 class="page-title"><?php echo apply_filters('the_title', $shop_page_title); ?></h1>
	<?php endif; ?>
	
	<?php echo apply_filters('the_content', $shop_page->post_content); ?>

	<?php woocommerce_get_template_part( 'loop', 'shop' ); ?>
	
	<?php do_action('woocommerce_pagination'); ?>

<?php do_action('woocommerce_after_main_content'); // </div></div> ?>

<?php do_action('woocommerce_sidebar'); ?>

<?php get_footer('shop'); ?>