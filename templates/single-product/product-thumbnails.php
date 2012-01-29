<?php
/**
 * Single Product Thumbnails
 */

global $post, $woocommerce;
?>
<div class="thumbnails">
	<?php	
		$thumb_id = get_post_thumbnail_id();
		$small_thumbnail_size = apply_filters('single_product_small_thumbnail_size', 'shop_thumbnail');
		$args = array(
			'post_type' 	=> 'attachment',
			'numberposts' 	=> -1,
			'post_status' 	=> null,
			'post_parent' 	=> $post->ID,
			'post__not_in'	=> array($thumb_id),
			'post_mime_type'=> 'image',
			'orderby'		=> 'menu_order',
			'order'			=> 'ASC'
		);
		$attachments = get_posts($args);
		if ($attachments) :
			$loop = 0;
			$columns = apply_filters('woocommerce_product_thumbnails_columns', 3);
			foreach ( $attachments as $attachment ) :
				
				if (get_post_meta($attachment->ID, '_woocommerce_exclude_image', true)==1) continue;
				
				$loop++;

				$_post = & get_post( $attachment->ID );
				$url = wp_get_attachment_url($_post->ID);
				$post_title = esc_attr($_post->post_title);
				$image = wp_get_attachment_image($attachment->ID, $small_thumbnail_size);

				echo '<a href="'.$url.'" title="'.$post_title.'" rel="thumbnails" class="zoom ';
				if ($loop==1 || ($loop-1)%$columns==0) echo 'first';
				if ($loop%$columns==0) echo 'last';
				echo '">'.$image.'</a>';

			endforeach;
		endif;
	?>
</div>