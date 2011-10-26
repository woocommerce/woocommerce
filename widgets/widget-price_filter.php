<?php 
/**
 * Price Filter Widget and related functions
 * 
 * Generates a range slider to filter products by price
 *
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
 
/**
 * Price filter Init
 */
add_action('init', 'woocommerce_price_filter_init');

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

/**
 * Price Filter post filter
 */
add_filter('loop_shop_post_in', 'woocommerce_price_filter');

function woocommerce_price_filter( $filtered_posts ) {

	if (isset($_GET['max_price']) && isset($_GET['min_price'])) :
		
		$matched_products = array();
		
		$matched_products_query = get_posts(array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'price',
					'value' => array( $_GET['min_price'], $_GET['max_price'] ),
					'type' => 'NUMERIC',
					'compare' => 'BETWEEN'
				)
			)
		));

		if ($matched_products_query) :
			foreach ($matched_products_query as $product) :
				$matched_products[] = $product->ID;
				if ($product->post_parent>0) $matched_products[] = $product->post_parent;
			endforeach;
		endif;
		
		// Filter the id's
		if (sizeof($filtered_posts)==0) :
			$filtered_posts = $matched_products;
			$filtered_posts[] = 0;
		else :
			$filtered_posts = array_intersect($filtered_posts, $matched_products);
			$filtered_posts[] = 0;
		endif;
		
	endif;
	
	return (array) $filtered_posts;
}

/**
 * Price Filter post Widget
 */
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

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		extract($args);
		
		if (!is_tax( 'product_cat' ) && !is_post_type_archive('product') && !is_tax( 'product_tag' )) return;
		
		global $_chosen_attributes, $wpdb, $woocommerce;
		
		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		echo $before_widget . $before_title . $title . $after_title;
		
		// Remember current filters/search
		$fields = '';
		
		if (get_search_query()) $fields = '<input type="hidden" name="s" value="'.get_search_query().'" />';
		if (isset($_GET['post_type'])) $fields .= '<input type="hidden" name="post_type" value="'.esc_attr( $_GET['post_type'] ).'" />';
		
		if ($_chosen_attributes) foreach ($_chosen_attributes as $attribute => $value) :
		
			$fields .= '<input type="hidden" name="'.esc_attr( str_replace('pa_', 'filter_', $attribute) ).'" value="'.esc_attr( implode(',', $value) ).'" />';
		
		endforeach;
		
		$min = 0;
		
		$max = ceil($wpdb->get_var("SELECT max(meta_value + 0) 
		FROM $wpdb->posts
		LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id
		WHERE meta_key = 'price' AND (
			$wpdb->posts.ID IN (".implode(',', $woocommerce->query->layered_nav_product_ids).") 
			OR (
				$wpdb->posts.post_parent IN (".implode(',', $woocommerce->query->layered_nav_product_ids).")
				AND $wpdb->posts.post_parent != 0
			)
		)"));
		
		echo '<form method="get" action="">
			<div class="price_slider_wrapper">
				<div class="price_slider"></div>
				<div class="price_slider_amount">
					<button type="submit" class="button">'.__('Filter', 'woothemes').'</button>'.__('Price:', 'woothemes').' <span></span>
					<input type="hidden" id="max_price" name="max_price" value="'.esc_attr( $max ).'" />
					<input type="hidden" id="min_price" name="min_price" value="'.esc_attr( $min ).'" />
					'.$fields.'
				</div>
			</div>
		</form>';
		
		echo $after_widget;
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = __('Filter by price', 'woothemes');
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
} // class WooCommerce_Widget_Price_Filter