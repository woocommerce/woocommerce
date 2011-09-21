<?php
/**
 * Product Search Widget
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */

class WooCommerce_Widget_Product_Search extends WP_Widget {

	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;
	
	/** constructor */
	function WooCommerce_Widget_Product_Search() {
	
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_product_search';
		$this->woo_widget_description = __( 'A Search box for products only.', 'woothemes' );
		$this->woo_widget_idbase = 'woocommerce_product_search';
		$this->woo_widget_name = __('WooCommerce Product Search', 'woothemes' );
		
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );
		
		/* Create the widget. */
		$this->WP_Widget('product_search', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		extract($args);

		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		echo $before_widget;
		
		if ($title) echo $before_title . $title . $after_title;
		
		?>
		<form role="search" method="get" id="searchform" action="<?php echo esc_url( home_url() ); ?>">
			<div>
				<label class="screen-reader-text" for="s"><?php _e('Search for:', 'woothemes'); ?></label>
				<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" placeholder="<?php _e('Search for products', 'woothemes'); ?>" />
				<input type="submit" id="searchsubmit" value="<?php _e('Search', 'woothemes'); ?>" />
				<input type="hidden" name="post_type" value="product" />
			</div>
		</form>
		<?php
		
		echo $after_widget;
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		return $instance;
	}

	/** @see WP_Widget->form */
	function form( $instance ) {
		global $wpdb;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}
} // WooCommerce_Widget_Product_Search