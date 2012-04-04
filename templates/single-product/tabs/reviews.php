<?php
/**
 * Reviews Tab
 */
 
global $woocommerce, $post;

if ( comments_open() ) : ?>
	<div class="panel" id="tab-reviews">
	
		<?php comments_template(); ?>
	
	</div>
<?php endif; ?>