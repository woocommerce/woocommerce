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
		$this->woo_widget_cssclass 		= 'widget_shopping_cart';
		$this->woo_widget_description 	= __( "Display the user's Cart in the sidebar.", 'woocommerce' );
		$this->woo_widget_idbase 		= 'woocommerce_widget_cart';
		$this->woo_widget_name 			= __( 'WooCommerce Cart', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget( 'shopping_cart', $this->woo_widget_name, $widget_ops );
	}

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		global $woocommerce;
		
		extract( $args );

		if ( is_cart() || is_checkout() ) return;

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __('Cart', 'woocommerce') : $instance['title'], $instance, $this->id_base );
		$hide_if_empty = empty( $instance['hide_if_empty'] )  ? 0 : 1;

		echo $before_widget;
		
		if ( $title ) 
			echo $before_title . $title . $after_title;

		echo '<ul class="cart_list product_list_widget ' . ( $hide_if_empty ? 'hide_cart_widget_if_empty' : '' ) . '">';
		
		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
		
			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {
				
				$_product = $cart_item['data'];

				if ( ! apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) 
					continue;
				
				if ( $_product->exists() && $cart_item['quantity'] > 0 ) {
				
					echo '<li><a href="' . get_permalink( $cart_item['product_id'] ) . '">';

					echo $_product->get_image();

					echo apply_filters('woocommerce_widget_cart_product_title', $_product->get_title(), $_product ) . '</a>';

	   				echo $woocommerce->cart->get_item_data( $cart_item );

	   				$product_price = get_option( 'woocommerce_display_cart_prices_excluding_tax' ) == 'yes' || $woocommerce->customer->is_vat_exempt() ? $_product->get_price_excluding_tax() : $_product->get_price();
							
					$product_price = apply_filters( 'woocommerce_cart_item_price_html', woocommerce_price( $product_price ), $cart_item, $cart_item_key ); 	
								
					echo '<span class="quantity">' . $cart_item['quantity'] . ' &times; ' . $product_price . '</span></li>';
				}
				
			}
			
		} else {
			echo '<li class="empty">' . __('No products in the cart.', 'woocommerce') . '</li>';
		}
		
		echo '</ul>';

		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
		
			echo '<p class="total"><strong>' . __('Subtotal', 'woocommerce') . ':</strong> ' . $woocommerce->cart->get_cart_subtotal() . '</p>';

			do_action( 'woocommerce_widget_shopping_cart_before_buttons' );

			echo '<p class="buttons"><a href="' . $woocommerce->cart->get_cart_url() . '" class="button">' . __('View Cart &rarr;', 'woocommerce') . '</a> <a href="' . $woocommerce->cart->get_checkout_url() . '" class="button checkout">' . __('Checkout &rarr;', 'woocommerce') . '</a></p>';
			
		}
		
		echo $after_widget;

		if ( $hide_if_empty && sizeof( $woocommerce->cart->get_cart() ) == 0 ) {
			$woocommerce->add_inline_js( "
				jQuery('.hide_cart_widget_if_empty').closest('.widget').hide();
				jQuery('body').bind('adding_to_cart', function(){
					jQuery(this).find('.hide_cart_widget_if_empty').closest('.widget').fadeIn();
				});
			" );
		}
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['hide_if_empty'] = empty( $new_instance['hide_if_empty'] ) ? 0 : 1;
		return $instance;
	}

	/** @see WP_Widget->form */
	function form( $instance ) {
		$hide_if_empty = empty( $instance['hide_if_empty'] ) ? 0 : 1;
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce') ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('hide_if_empty') ); ?>" name="<?php echo esc_attr( $this->get_field_name('hide_if_empty') ); ?>"<?php checked( $hide_if_empty ); ?> />
		<label for="<?php echo $this->get_field_id('hide_if_empty'); ?>"><?php _e( 'Hide if cart is empty', 'woocommerce' ); ?></label></p>
		<?php
	}

} // class WooCommerce_Widget_Cart