<?php
/**
 * Product Search Widget
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */

class WooCommerce_Widget_Product_Search extends WP_Widget {

	/** constructor */
	function WooCommerce_Widget_Product_Search() {
		$widget_ops = array( 'description' => __( "Search box for products only.", 'woothemes') );
		parent::WP_Widget('product_search', __('Product Search', 'woothemes'), $widget_ops);
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract($args);

		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		echo $before_widget;
		
		if ($title) echo $before_title . $title . $after_title;
		
		?>
		<form role="search" method="get" id="searchform" action="<?php echo home_url(); ?>">
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

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		global $wpdb;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}
} // WooCommerce_Widget_Product_Search