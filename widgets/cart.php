<?php
/**
 * Shopping Cart Widget
 *
 * Displays shopping cart widget
 *
 * @package		JigoShop
 * @category	Widgets
 * @author		Jigowatt
 * @since		1.0
 * 
 */
 
class Jigoshop_Widget_Cart extends WP_Widget {
	
	/** constructor */
	function Jigoshop_Widget_Cart() {
		$widget_ops = array( 'description' => __( "Shopping Cart for the sidebar.", 'jigoshop') );
		parent::WP_Widget('shopping_cart', __('Shopping Cart', 'jigoshop'), $widget_ops);
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {

		if (is_cart()) return;
		
		extract($args);
		if ( !empty($instance['title']) ) $title = $instance['title']; else $title = __('Cart', 'jigoshop');
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		
		echo '<ul class="cart_list">';
		if (sizeof(jigoshop_cart::$cart_contents)>0) : foreach (jigoshop_cart::$cart_contents as $item_id => $values) :
			$_product = $values['data'];
			if ($_product->exists() && $values['quantity']>0) :
				echo '<li><a href="'.get_permalink($item_id).'">';
				
				if (has_post_thumbnail($item_id)) echo get_the_post_thumbnail($item_id, 'shop_tiny'); 
				else echo '<img src="'.jigoshop::plugin_url(). '/assets/images/placeholder.png" alt="Placeholder" width="'.jigoshop::get_var('shop_tiny_w').'" height="'.jigoshop::get_var('shop_tiny_h').'" />'; 
				
				echo apply_filters('jigoshop_cart_widget_product_title', $_product->get_title(), $_product).'</a> '.$values['quantity'].' &times; '.jigoshop_price($_product->get_price()).'</li>';
			endif;
		endforeach; 
		else: echo '<li class="empty">'.__('No products in the cart.','jigoshop').'</li>'; endif;
		echo '</ul>';
		
		if (sizeof(jigoshop_cart::$cart_contents)>0) :
			echo '<p class="total"><strong>';
			
			if (get_option('js_prices_include_tax')=='yes') :
				_e('Total', 'jigoshop');
			else :
				_e('Subtotal', 'jigoshop');
			endif;
	
			echo ':</strong> '.jigoshop_cart::get_cart_total();
			
			echo '</p>';
			
			do_action( 'jigoshop_widget_shopping_cart_before_buttons' );
			
			echo '<p class="buttons"><a href="'.jigoshop_cart::get_cart_url().'" class="button">'.__('View Cart &rarr;','jigoshop').'</a> <a href="'.jigoshop_cart::get_checkout_url().'" class="button checkout">'.__('Checkout &rarr;','jigoshop').'</a></p>';
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
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'jigoshop') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
	<?php
	}

} // class Jigoshop_Widget_Cart