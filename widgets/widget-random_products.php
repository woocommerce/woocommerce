<?php
/**
 * Random Products Widget
 *
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */

class WooCommerce_Widget_Random_Products extends WP_Widget {

	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;

	/** constructor */
	function WooCommerce_Widget_Random_Products() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_random_products';
		$this->woo_widget_description = __( 'Display a list of random products on your site.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_random_products';
		$this->woo_widget_name = __('WooCommerce Random Products', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('random_products', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget */
	function widget($args, $instance) {
		global $woocommerce;

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Random Products', 'woocommerce') : $instance['title'], $instance, $this->id_base);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		$number = min(15, max(1, $number));

    $show_variations = (bool) $instance['show_variations'];

    $query_args = array('posts_per_page' => $number, 'nopaging' => 0, 'post_status' => 'publish', 'post_type' => 'product', 'orderby' => 'rand');

    if($show_variations=='0'){
      $query_args['meta_query'] = array(
			  array(
				  'key' => '_visibility',
				  'value' => array('catalog', 'visible'),
				  'compare' => 'IN'
			  )
		  );
		  $query_args['parent'] = '0';
    }

		$r = new WP_Query($query_args);

		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul class="product_list_widget">
		<?php  while ($r->have_posts()) : $r->the_post(); global $product; ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>">
			<?php if (has_post_thumbnail()) the_post_thumbnail('shop_thumbnail'); else echo '<img src="'.$woocommerce->plugin_url().'/assets/images/placeholder.png" alt="Placeholder" width="'.$woocommerce->get_image_size('shop_thumbnail_image_width').'" height="'.$woocommerce->get_image_size('shop_thumbnail_image_height').'" />'; ?>
			<?php if ( get_the_title() ) the_title(); else the_ID(); ?>
		</a> <?php echo $product->get_price_html(); ?></li>
		<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		endif;
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = absint($new_instance['number']);
		$instance['show_variations'] = ! empty($new_instance['show_variations']);

		return $instance;
	}

	/** @see WP_Widget->form */
	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 5;

		$show_variations = isset( $instance['show_variations'] ) ? (bool) $instance['show_variations'] : false;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce'); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of products to show:', 'woocommerce'); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id('number') ); ?>" name="<?php echo esc_attr( $this->get_field_name('number') ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" size="3" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('show_variations') ); ?>" name="<?php echo esc_attr( $this->get_field_name('show_variations') ); ?>"<?php checked( $show_variations ); ?> />
		<label for="<?php echo $this->get_field_id('show_variations'); ?>"><?php _e( 'Show hidden product variations', 'woocommerce' ); ?></label><br />

<?php
	}
} // class WooCommerce_Widget_Random_Products
