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
		$this->woo_widget_description = __( 'A Search box for products only.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_product_search';
		$this->woo_widget_name = __('WooCommerce Product Search', 'woocommerce' );
		
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
		
		get_product_search_form();
		
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
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}
} // WooCommerce_Widget_Product_Search