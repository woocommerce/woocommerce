<?php 
/**
 * Price Filter Widget
 * 
 * Generates a range slider to filter products by price
 *
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
 
function woocommerce_price_filter_init() {
	
	unset($_SESSION['min_price']);
	unset($_SESSION['max_price']);
	
	if (isset($_GET['min_price'])) :
		
		$_SESSION['min_price'] = $_GET['min_price'];
		
	endif;
	if (isset($_GET['max_price'])) :
		
		$_SESSION['max_price'] = $_GET['max_price'];
		
	endif;
	
}
add_action('init', 'woocommerce_price_filter_init');

class WooCommerce_Widget_Price_Filter extends WP_Widget {

	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;
	
	/** constructor */
	function WooCommerce_Widget_Price_Filter() {
		
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_price_filter';
		$this->woo_widget_description = __( 'Shows a price filter slider in a widget which lets you narrow down the list of shown products when viewing product categories.', 'woothemes' );
		$this->woo_widget_idbase = 'woocommerce_price_filter';
		$this->woo_widget_name = __('WooCommerce Price Filter', 'woothemes' );
		
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );
		
		/* Create the widget. */
		$this->WP_Widget('price_filter', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract($args);
		
		if (!is_tax( 'product_cat' ) && !is_post_type_archive('product') && !is_tax( 'product_tag' )) return;
		
		global $_chosen_attributes, $wpdb, $woocommerce_query;
		
		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		echo $before_widget . $before_title . $title . $after_title;
		
		// Remember current filters/search
		$fields = '';
		
		if (get_search_query()) $fields = '<input type="hidden" name="s" value="'.get_search_query().'" />';
		if (isset($_GET['post_type'])) $fields .= '<input type="hidden" name="post_type" value="'.$_GET['post_type'].'" />';
		
		if ($_chosen_attributes) foreach ($_chosen_attributes as $attribute => $value) :
		
			$fields .= '<input type="hidden" name="'.str_replace('pa_', 'filter_', $attribute).'" value="'.implode(',', $value).'" />';
		
		endforeach;
		
		$min = 0;
		
		$max = ceil($wpdb->get_var("SELECT max(meta_value + 0) 
		FROM $wpdb->posts
		LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id
		WHERE meta_key = 'price' AND (
			$wpdb->posts.ID IN (".implode(',', $woocommerce_query['layered_nav_product_ids']).") 
			OR (
				$wpdb->posts.post_parent IN (".implode(',', $woocommerce_query['layered_nav_product_ids']).")
				AND $wpdb->posts.post_parent != 0
			)
		)"));
		
		if (defined('SHOP_IS_ON_FRONT')) :
			$link = '';
		elseif (is_post_type_archive('product') || is_page( get_option('woocommerce_shop_page_id') )) :
			$link = get_post_type_archive_link('product');
		else :					
			$link = get_term_link( get_query_var('term'), get_query_var('taxonomy') );
		endif;
		
		echo '<form method="get" action="'.$link.'">
			<div class="price_slider_wrapper">
				<div class="price_slider"></div>
				<div class="price_slider_amount">
					<button type="submit" class="button">Filter</button>'.__('Price: ', 'woothemes').'<span></span>
					<input type="hidden" id="max_price" name="max_price" value="'.$max.'" />
					<input type="hidden" id="min_price" name="min_price" value="'.$min.'" />
					'.$fields.'
				</div>
			</div>
		</form>';
		
		echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = __('Filter by price', 'woothemes');
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
} // class WooCommerce_Widget_Price_Filter