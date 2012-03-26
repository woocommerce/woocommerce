<?php
/**
 * Single Product Short Description
 */

global $post;
?>
<?php if ($post->post_excerpt) : ?>

	<div itemprop="description">
	
		<?php echo apply_filters( 'the_content', $post->post_excerpt ) ?>
	
	</div>

<?php endif; ?>