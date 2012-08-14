<?php
/**
 * Product Search Widget
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.6.4
 * @extends 	WP_Widget
 */
class WooCommerce_Widget_Product_Search extends WP_Widget {

	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;

	/**
	 * constructor
	 *
	 * @access public
	 * @return void
	 */
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

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		extract($args);

		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;

		if ($title) echo $before_title . $title . $after_title;

		get_product_search_form();

		echo $after_widget;
	}

	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		return $instance;
	}

	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		global $wpdb;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}
}