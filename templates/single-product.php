<?php get_header('shop'); ?>
	  
<?php do_action('woocommerce_before_main_content'); ?>

	<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
		
		<?php do_action('woocommerce_before_single_product'); ?>
	
		<div itemscope itemtype="http://schema.org/Product" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
			
			<?php do_action('woocommerce_before_single_product_summary'); ?>
			
			<div class="summary">
				
				<h1 itemprop="name" class="product_title page-title"><?php the_title(); ?></h1>
				
				<?php do_action( 'woocommerce_single_product_summary'); ?>
	
			</div>
			
			<?php do_action('woocommerce_after_single_product_summary'); ?>
	
		</div>
			
		<?php do_action('woocommerce_after_single_product'); ?>
	
	<?php endwhile; ?>

<?php do_action('woocommerce_after_main_content'); ?>

<?php do_action('woocommerce_sidebar'); ?>

<?php get_footer('shop'); ?>