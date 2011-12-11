<?php
/**
 * Single Product Short Description
 */

global $post;
?>
<?php if ($post->post_excerpt) : ?>

	<div itemprop="description">
	
		<?php echo wpautop(wptexturize($post->post_excerpt)) ?>
	
	</div>

<?php endif; ?>