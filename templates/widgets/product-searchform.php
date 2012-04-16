<form role="search" method="get" id="searchform" action="<?php echo esc_url( home_url() ); ?>">
	<div>
		<label class="screen-reader-text" for="s"><?php _e('Search for:', 'woocommerce'); ?></label>
		<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" placeholder="<?php esc_attr_e('Search for products', 'woocommerce'); ?>" />
		<input type="submit" id="searchsubmit" value="<?php esc_attr_e('Search', 'woocommerce'); ?>" />
		<input type="hidden" name="post_type" value="product" />
	</div>
</form>
