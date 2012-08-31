<?php
/**
 * Description tab
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

global $post;

if ( $post->post_content ) : ?>
	<li class="description_tab"><a href="#tab-description"><?php _e('Description', 'woocommerce'); ?></a></li>
<?php endif; ?>