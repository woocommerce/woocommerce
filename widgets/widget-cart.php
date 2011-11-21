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
	
	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;

	/** constructor */
	function WooCommerce_Widget_Cart() {
	
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_shopping_cart';
		$this->woo_widget_description = __( "Display the user's Shopping Cart in the sidebar.", 'woothemes' );
		$this->woo_widget_idbase = 'woocommerce_shopping_cart';
		$this->woo_widget_name = __('WooCommerce Shopping Cart', 'woothemes' );
		
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );
		
		/* Create the widget. */
		$this->WP_Widget('shopping_cart', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		global $woocommerce;
		
		if (is_cart() || is_checkout()) return;
		
		extract($args);
		if ( !empty($instance['title']) ) $title = $instance['title']; else $title = __('Cart', 'woothemes');
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		
		echo '<ul class="cart_list product_list_widget">';
		if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
			$_product = $cart_item['data'];
			if ($_product->exists() && $cart_item['quantity']>0) :
				echo '<li><a href="'.get_permalink($cart_item['product_id']).'">';
				
				echo $_product->get_image();
				
				echo apply_filters('woocommerce_cart_widget_product_title', $_product->get_title(), $_product).'</a>';
				
   				echo $woocommerce->cart->get_item_data( $cart_item );
				
				echo '<span class="quantity">' .$cart_item['quantity'].' &times; '.woocommerce_price($_product->get_price()).'</span></li>';
			endif;
		endforeach; 
		else: echo '<li class="empty">'.__('No products in the cart.', 'woothemes').'</li>'; endif;
		echo '</ul>';
		
		if (sizeof($woocommerce->cart->get_cart())>0) :
			echo '<p class="total"><strong>';
			
			if (get_option('js_prices_include_tax')=='yes') :
				_e('Total', 'woothemes');
			else :
				_e('Subtotal', 'woothemes');
			endif;
	
			echo ':</strong> '.$woocommerce->cart->get_cart_total();
			
			echo '</p>';
			
			do_action( 'woocommerce_widget_shopping_cart_before_buttons' );
			
			echo '<p class="buttons"><a href="'.$woocommerce->cart->get_cart_url().'" class="button">'.__('View Cart &rarr;', 'woothemes').'</a> <a href="'.$woocommerce->cart->get_checkout_url().'" class="button checkout">'.__('Checkout &rarr;', 'woothemes').'</a></p>';
		endif;
		echo $after_widget;
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		return $instance;
	}

	/** @see WP_Widget->form */
	function form( $instance ) {
	?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
	<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
	<?php
	}

} // class WooCommerce_Widget_Cart