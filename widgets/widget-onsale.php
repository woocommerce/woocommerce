<?php
/**
 * On Sale Widget
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.6.4
 * @extends 	WP_Widget
 */
class WooCommerce_Widget_On_Sale extends WP_Widget {

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
	function WooCommerce_Widget_On_Sale() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_onsale';
		$this->woo_widget_description = __( 'Display a list of your on-sale products on your site.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_onsale';
		$this->woo_widget_name = __('WooCommerce On-sale', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('onsale', $this->woo_widget_name, $widget_ops);

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
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
		global $wp_query, $woocommerce;

		$cache = wp_cache_get('widget_onsale', 'widget');

		if ( !is_array($cache) ) $cache = array();

		if ( isset($cache[$args['widget_id']]) ) {
			echo $cache[$args['widget_id']];
			return;
		}

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('On Sale', 'woocommerce') : $instance['title'], $instance, $this->id_base);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;

		// Get products on sale
		if ( false === ( $product_ids_on_sale = get_transient( 'wc_products_onsale' ) ) ) {

			$meta_query = array();

		    $meta_query[] = array(
		    	'key' => '_sale_price',
		        'value' 	=> 0,
				'compare' 	=> '>',
				'type'		=> 'NUMERIC'
		    );

			$on_sale = get_posts(array(
				'post_type' 		=> array('product', 'product_variation'),
				'posts_per_page' 	=> -1,
				'post_status' 		=> 'publish',
				'meta_query' 		=> $meta_query,
				'fields' 			=> 'id=>parent'
			));

			$product_ids 	= array_keys( $on_sale );
			$parent_ids		= array_values( $on_sale );

			// Check for scheduled sales which have not started
			foreach ( $product_ids as $key => $id )
				if ( get_post_meta( $id, '_sale_price_dates_from', true ) > current_time('timestamp') )
					unset( $product_ids[ $key ] );

			$product_ids_on_sale = array_unique( array_merge( $product_ids, $parent_ids ) );

			set_transient( 'wc_products_onsale', $product_ids_on_sale );

		}

		$product_ids_on_sale[] = 0;

		$meta_query = array();
		$meta_query[] = $woocommerce->query->visibility_meta_query();
	    $meta_query[] = $woocommerce->query->stock_status_meta_query();

    	$query_args = array(
    		'posts_per_page' 	=> $number,
    		'no_found_rows' => 1,
    		'post_status' 	=> 'publish',
    		'post_type' 	=> 'product',
    		'orderby' 		=> 'date',
    		'order' 		=> 'ASC',
    		'meta_query' 	=> $meta_query,
    		'post__in'		=> $product_ids_on_sale
    	);

		$r = new WP_Query($query_args);

		if ( $r->have_posts() ) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul class="product_list_widget">
		<?php  while ($r->have_posts()) : $r->the_post(); global $product; ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>">
			<?php if (has_post_thumbnail()) the_post_thumbnail('shop_thumbnail'); else echo '<img src="'. woocommerce_placeholder_img_src() .'" alt="Placeholder" width="'.$woocommerce->get_image_size('shop_thumbnail_image_width').'" height="'.$woocommerce->get_image_size('shop_thumbnail_image_height').'" />'; ?>
			<?php if ( get_the_title() ) the_title(); else the_ID(); ?>
		</a> <?php echo $product->get_price_html(); ?></li>
		<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		endif;

		$content = ob_get_clean();

		if ( isset( $args['widget_id'] ) ) $cache[$args['widget_id']] = $content;

		echo $content;

		wp_cache_set('widget_onsale', $cache, 'widget');
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
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_onsale']) ) delete_option('widget_onsale');

		return $instance;
	}

	/**
	 * flush_widget_cache function.
	 *
	 * @access public
	 * @return void
	 */
	function flush_widget_cache() {
		wp_cache_delete('widget_onsale', 'widget');
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
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] ) $number = 5;

		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce'); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of products to show:', 'woocommerce'); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id('number') ); ?>" name="<?php echo esc_attr( $this->get_field_name('number') ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" size="3" /></p>

		<?php
	}
}
