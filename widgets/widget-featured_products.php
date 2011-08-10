<?php
/**
 * Featured Products Widget
 *
 * Gets and displays featured products in an unordered list
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
class WooCommerce_Widget_Featured_Products extends WP_Widget {

	/** constructor */
	function WooCommerce_Widget_Featured_Products() {
		$widget_ops = array('classname' => 'widget_featured_products', 'description' => __( "Featured products on your site", 'woothemes') );
		parent::WP_Widget('featured-products', __('WooCommerce Featured Products', 'woothemes'), $widget_ops);
		$this->alt_option_name = 'widget_featured_products';

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	/** @see WP_Widget::widget */
	function widget($args, $instance) {
		$cache = wp_cache_get('widget_featured_products', 'widget');

		if ( !is_array($cache) ) $cache = array();

		if ( isset($cache[$args['widget_id']]) ) {
			echo $cache[$args['widget_id']];
			return;
		}

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Featured Products', 'woothemes') : $instance['title'], $instance, $this->id_base);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;

		$featured_posts = get_posts(array('numberposts' => $number, 'post_status' => 'publish', 'post_type' => 'product', 'meta_key' => 'featured', 'meta_value' => 'yes' ));
		if ($featured_posts) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul class="product_list_widget">
		<?php foreach ($featured_posts as $r) : $_product = &new woocommerce_product( $r->ID ); ?>
		
		<li><a href="<?php echo get_permalink( $r->ID ) ?>" title="<?php echo esc_attr($r->post_title ? $r->post_title : $r->ID); ?>">
			<?php if (has_post_thumbnail( $r->ID )) echo get_the_post_thumbnail($r->ID, 'shop_tiny'); else echo '<img src="'.woocommerce::plugin_url().'/assets/images/placeholder.png" alt="Placeholder" width="'.woocommerce::get_var('shop_tiny_w').'px" height="'.woocommerce::get_var('shop_tiny_h').'px" />'; ?>
			<?php if ( $r->post_title ) echo $r->post_title; else echo $r->ID; ?>
		</a> <?php echo $_product->get_price_html(); ?></li>
		
		<?php endforeach; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php

		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('widget_featured_products', $cache, 'widget');
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_featured_products']) ) delete_option('widget_featured_products');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_featured_products', 'widget');
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 2;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of products to show:', 'woothemes'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
} // class WooCommerce_Widget_Featured_Products