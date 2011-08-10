<?php
/**
 * Tag Cloud Widget
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
 
class WooCommerce_Widget_Tag_Cloud extends WP_Widget {

	/** constructor */
	function WooCommerce_Widget_Tag_Cloud() {
		$widget_ops = array( 'description' => __( "Your most used product tags in cloud format", 'woothemes') );
		parent::WP_Widget('tag_cloud', __('WooCommerce Product Tag Cloud', 'woothemes'), $widget_ops);
	}
	
	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract($args);
		$current_taxonomy = $this->_get_current_taxonomy($instance);
		if ( !empty($instance['title']) ) {
			$title = $instance['title'];
		} else {
			if ( 'product_tag' == $current_taxonomy ) {
				$title = __('Product Tags', 'woothemes');
			} else {
				$tax = get_taxonomy($current_taxonomy);
				$title = $tax->labels->name;
			}
		}
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		echo '<div class="tagcloud">';
		wp_tag_cloud( apply_filters('widget_tag_cloud_args', array('taxonomy' => $current_taxonomy) ) );
		echo "</div>\n";
		echo $after_widget;
	}
	
	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['taxonomy'] = stripslashes($new_instance['taxonomy']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		$current_taxonomy = $this->_get_current_taxonomy($instance);
?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
	<?php
	}

	function _get_current_taxonomy($instance) {
		return 'product_tag';
	}
} // class WooCommerce_Widget_Tag_Cloud