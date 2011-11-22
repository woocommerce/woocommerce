<?php get_header('shop'); ?>

<?php do_action('woocommerce_before_main_content'); // <div id="container"><div id="content" role="main"> ?>

	<?php $term = get_term_by( 'slug', get_query_var($wp_query->query_vars['taxonomy']), $wp_query->query_vars['taxonomy']); ?>
			
	<h1 class="page-title"><?php echo wptexturize($term->name); ?></h1>
		
	<?php if ($term->description) : ?><div class="term_description"><?php echo wpautop(wptexturize($term->description)); ?></div><?php endif; ?>
	
	<?php woocommerce_get_template_part( 'loop', 'shop' ); ?>
	
	<?php do_action('woocommerce_pagination'); ?>

<?php do_action('woocommerce_after_main_content'); // </div></div> ?>

<?php do_action('woocommerce_sidebar'); ?>

<?php get_footer('shop'); ?>