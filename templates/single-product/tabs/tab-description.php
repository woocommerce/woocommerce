<?php
/**
 * Description tab
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post;

if ( $post->post_content ) : ?>
	<li class="description_tab"><a href="#tab-description"><?php echo apply_filters('woocommerce_product_description_tab_title', __( 'Description', 'woocommerce' )) ?></a></li>
<?php endif; ?>