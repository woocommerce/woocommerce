<?php get_header('shop'); ?>
	  
<?php do_action('woocommerce_before_main_content'); // <div id="container"><div id="content" role="main"> ?>

	<?php if ( have_posts() ) while ( have_posts() ) : the_post(); global $_product; $_product = &new woocommerce_product( $post->ID ); ?>
		
		<?php do_action('woocommerce_before_single_product', $post, $_product); ?>
	
		<div itemscope itemtype="http://schema.org/Product" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
			
			<?php do_action('woocommerce_before_single_product_summary', $post, $_product); ?>
			
			<div class="summary">
				
				<h1 itemprop="name" class="product_title page-title"><?php the_title(); ?></h1>
				
				<?php do_action( 'woocommerce_single_product_summary', $post, $_product ); ?>
	
			</div>
			
			<?php do_action('woocommerce_after_single_product_summary', $post, $_product); ?>
	
		</div>
			
		<?php do_action('woocommerce_after_single_product', $post, $_product); ?>
	
	<?php endwhile; ?>

<?php do_action('woocommerce_after_main_content'); // </div></div> ?>

<?php do_action('woocommerce_sidebar'); ?>

<?php get_footer('shop'); ?>