<?php
/**
 * Single Product Short Description
 */

global $post;
?>
<?php if ($post->post_excerpt) : ?>

	<div itemprop="description">
	
		<?php echo apply_filters( 'woocommerce_short_description', $post->post_excerpt ) ?>
	
	</div>

<?php endif; ?>