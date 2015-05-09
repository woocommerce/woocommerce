<form role="search" method="get" class="woocommerce-product-search" action="<?php echo esc_url( home_url( '/'  ) ); ?>">
	<label class="screen-reader-text" for="s"><?php _e( 'Search for:', 'woocommerce' ); ?></label>
	<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( _e( 'Search Products', 'woocommerce' ).'&hellip;', 'placeholder', 'woocommerce' ); ?>" value="<?php echo get_search_query(); ?>" name="s" title="<?php echo esc_attr_x( _e( 'Search for:', 'woocommerce' ), 'label', 'woocommerce' ); ?>" />
	<input type="submit" value="<?php echo esc_attr_x( _e( 'Search', 'woocommerce' ), 'submit button', 'woocommerce' ); ?>" />
	<input type="hidden" name="post_type" value="product" />
</form>
