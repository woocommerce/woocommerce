<?php
/**
 * Shopping Cart Widget
 *
 * Displays shopping cart widget
 *
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
class WooCommerce_Widget_Cart extends WP_Widget {
	
	/** constructor */
	function WooCommerce_Widget_Cart() {
		$widget_ops = array( 'description' => __( "Shopping Cart for the sidebar.", 'woothemes') );
		parent::WP_Widget('shopping_cart', __('WooCommerce Shopping Cart', 'woothemes'), $widget_ops);
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {

		if (is_cart()) return;
		
		extract($args);
		if ( !empty($instance['title']) ) $title = $instance['title']; else $title = __('Cart', 'woothemes');
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		
		echo '<ul class="cart_list">';
		if (sizeof(woocommerce_cart::$cart_contents)>0) : foreach (woocommerce_cart::$cart_contents as $item_id => $values) :
			$_product = $values['data'];
			if ($_product->exists() && $values['quantity']>0) :
				echo '<li><a href="'.get_permalink($item_id).'">';
				
				if (has_post_thumbnail($item_id)) echo get_the_post_thumbnail($item_id, 'shop_tiny'); 
				else echo '<img src="'.woocommerce::plugin_url(). '/assets/images/placeholder.png" alt="Placeholder" width="'.woocommerce::get_var('shop_tiny_w').'" height="'.woocommerce::get_var('shop_tiny_h').'" />'; 
				
				echo apply_filters('woocommerce_cart_widget_product_title', $_product->get_title(), $_product).'</a> '.$values['quantity'].' &times; '.woocommerce_price($_product->get_price()).'</li>';
			endif;
		endforeach; 
		else: echo '<li class="empty">'.__('No products in the cart.', 'woothemes').'</li>'; endif;
		echo '</ul>';
		
		if (sizeof(woocommerce_cart::$cart_contents)>0) :
			echo '<p class="total"><strong>';
			
			if (get_option('js_prices_include_tax')=='yes') :
				_e('Total', 'woothemes');
			else :
				_e('Subtotal', 'woothemes');
			endif;
	
			echo ':</strong> '.woocommerce_cart::get_cart_total();
			
			echo '</p>';
			
			do_action( 'woocommerce_widget_shopping_cart_before_buttons' );
			
			echo '<p class="buttons"><a href="'.woocommerce_cart::get_cart_url().'" class="button">'.__('View Cart &rarr;', 'woothemes').'</a> <a href="'.woocommerce_cart::get_checkout_url().'" class="button checkout">'.__('Checkout &rarr;', 'woothemes').'</a></p>';
		endif;
		echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
	?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
	<?php
	}

} // class WooCommerce_Widget_Cart