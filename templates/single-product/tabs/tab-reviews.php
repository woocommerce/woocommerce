<?php if ( comments_open() ) : ?>
	<li class="reviews_tab"><a href="#tab-reviews"><?php _e('Reviews', 'woocommerce'); ?><?php echo comments_number(' (0)', ' (1)', ' (%)'); ?></a></li>
<?php endif; ?>