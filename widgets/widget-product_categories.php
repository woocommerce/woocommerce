<?php
/**
 * Product Search Widget
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
 
class WooCommerce_Widget_Product_Categories extends WP_Widget {

	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;
	var $cat_ancestors;
	var $current_cat;
	
	/** constructor */
	function WooCommerce_Widget_Product_Categories() {
	
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_product_categories';
		$this->woo_widget_description = __( 'A list or dropdown of product categories.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_product_categories';
		$this->woo_widget_name = __('WooCommerce Product Categories', 'woocommerce' );
		
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );
		
		/* Create the widget. */
		$this->WP_Widget('product_categories', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Product Categories', 'woocommerce' ) : $instance['title'], $instance, $this->id_base);
		$c = $instance['count'] ? '1' : '0';
		$h = $instance['hierarchical'] ? true : false;
		$s = (isset($instance['show_children_only']) && $instance['show_children_only']) ? '1' : '0';
		$d = $instance['dropdown'] ? '1' : '0';
		$o = isset($instance['orderby']) ? $instance['orderby'] : 'order';

		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;

		$cat_args = array('show_count' => $c, 'hierarchical' => $h, 'taxonomy' => 'product_cat');
		
		if ( $o == 'order' ) {
			
			$cat_args['menu_order'] = 'asc';
			
		} else {
			
			$cat_args['orderby'] = 'title';
			
		}
		
		if ( $d ) {

			// Stuck with this until a fix for http://core.trac.wordpress.org/ticket/13258
			woocommerce_product_dropdown_categories( $c, $h, 0 );
			
			?>
			<script type='text/javascript'>
			/* <![CDATA[ */
				var dropdown = document.getElementById("dropdown_product_cat");
				function onCatChange() {
					if ( dropdown.options[dropdown.selectedIndex].value !=='' ) {
						location.href = "<?php echo home_url(); ?>/?product_cat="+dropdown.options[dropdown.selectedIndex].value;
					}
				}
				dropdown.onchange = onCatChange;
			/* ]]> */
			</script>
			<?php
			
		} else {
			
			global $wp_query, $post, $woocommerce;
			
			$this->current_cat = false;
			$this->cat_ancestors = array();
				
			if ( is_tax('product_cat') ) :
			
				$this->current_cat = $wp_query->queried_object;
				$this->cat_ancestors = get_ancestors( $this->current_cat->term_id, 'product_cat' );
			
			elseif ( is_singular('product') ) :
				
				$product_category = wp_get_post_terms( $post->ID, 'product_cat' ); 
				
				if ($product_category) :
					$this->current_cat = end($product_category);
					$this->cat_ancestors = get_ancestors( $this->current_cat->term_id, 'product_cat' );
				endif;
				
			endif;
			
			include_once( $woocommerce->plugin_path() . '/classes/walkers/class-product-cat-list-walker.php' );
			
			$cat_args['walker'] 			= new WC_Product_Cat_List_Walker;
			$cat_args['title_li'] 			= '';
			$cat_args['show_children_only']	= ( isset( $instance['show_children_only'] ) && $instance['show_children_only'] ) ? 1 : 0;
			$cat_args['pad_counts'] 		= 1;
			$cat_args['show_option_none'] 	= __('No product categories exist.', 'woocommerce');
			$cat_args['current_category']	= ( $this->current_cat ) ? $this->current_cat->term_id : '';
			$cat_args['current_category_ancestors']	= $this->cat_ancestors;
			
			echo '<ul class="product-categories">';
			
			wp_list_categories( apply_filters( 'woocommerce_product_categories_widget_args', $cat_args ) );
			
			echo '</ul>';

		}

		echo $after_widget;
	}

	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['orderby'] = strip_tags($new_instance['orderby']);
		$instance['count'] = !empty($new_instance['count']) ? 1 : 0;
		$instance['hierarchical'] = !empty($new_instance['hierarchical']) ? true : false;
		$instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;
		$instance['show_children_only'] = !empty($new_instance['show_children_only']) ? 1 : 0;

		return $instance;
	}
	
	/** @see WP_Widget->form */
	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = esc_attr( $instance['title'] );
		$orderby = isset( $instance['orderby'] ) ? $instance['orderby'] : 'order';
		$count = isset($instance['count']) ? (bool) $instance['count'] :false;
		$hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
		$show_children_only = isset( $instance['show_children_only'] ) ? (bool) $instance['show_children_only'] : false;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'woocommerce' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('Order by:', 'woocommerce') ?></label>
		<select id="<?php echo esc_attr( $this->get_field_id('orderby') ); ?>" name="<?php echo esc_attr( $this->get_field_name('orderby') ); ?>">
			<option value="order" <?php selected($orderby, 'order'); ?>><?php _e('Category Order', 'woocommerce'); ?></option>
			<option value="name" <?php selected($orderby, 'name'); ?>><?php _e('Name', 'woocommerce'); ?></option>
		</select></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('dropdown') ); ?>" name="<?php echo esc_attr( $this->get_field_name('dropdown') ); ?>"<?php checked( $dropdown ); ?> />
		<label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e( 'Show as dropdown', 'woocommerce' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('count') ); ?>" name="<?php echo esc_attr( $this->get_field_name('count') ); ?>"<?php checked( $count ); ?> />
		<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e( 'Show post counts', 'woocommerce' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('hierarchical') ); ?>" name="<?php echo esc_attr( $this->get_field_name('hierarchical') ); ?>"<?php checked( $hierarchical ); ?> />
		<label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy', 'woocommerce' ); ?></label><br/>
		
		<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('show_children_only') ); ?>" name="<?php echo esc_attr( $this->get_field_name('show_children_only') ); ?>"<?php checked( $show_children_only ); ?> />
		<label for="<?php echo $this->get_field_id('show_children_only'); ?>"><?php _e( 'Show children of current category only', 'woocommerce' ); ?></label></p>
<?php
	}

} // class WooCommerce_Widget_Product_Categories