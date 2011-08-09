<?php get_header('shop'); ?>

<?php do_action('jigoshop_before_main_content'); // <div id="container"><div id="content" role="main"> ?>

	<?php $term = get_term_by( 'slug', get_query_var($wp_query->query_vars['taxonomy']), $wp_query->query_vars['taxonomy']); ?>
			
	<h1 class="page-title"><?php echo wptexturize($term->name); ?></h1>
		
	<?php echo wpautop(wptexturize($term->description)); ?>
	
	<?php jigoshop_get_template_part( 'loop', 'shop' ); ?>
	
	<?php do_action('jigoshop_pagination'); ?>

<?php do_action('jigoshop_after_main_content'); // </div></div> ?>

<?php do_action('jigoshop_sidebar'); ?>

<?php get_footer('shop'); ?>