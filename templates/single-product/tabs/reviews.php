<?php
/**
 * Reviews tab
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

global $woocommerce, $post;

if ( comments_open() ) : ?>
	<div class="panel entry-content" id="tab-reviews">

		<?php comments_template(); ?>

	</div>
<?php endif; ?>