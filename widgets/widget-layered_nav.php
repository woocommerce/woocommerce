<?php
/**
 * Layered Navigation Widget and related functions
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
 
/**
 * Layered Nav Init
 */
add_action('init', 'woocommerce_layered_nav_init', 1);

function woocommerce_layered_nav_init() {

	global $_chosen_attributes, $wpdb, $woocommerce;
	
	$_chosen_attributes = array();
	
	$attribute_taxonomies = $woocommerce->attribute_taxonomies;
	if ( $attribute_taxonomies ) :
		foreach ($attribute_taxonomies as $tax) :
	    	
	    	$attribute = strtolower(sanitize_title($tax->attribute_name));
	    	$taxonomy = $woocommerce->attribute_taxonomy_name($attribute);
	    	$name = 'filter_' . $attribute;
	    	
	    	if (isset($_GET[$name]) && taxonomy_exists($taxonomy)) $_chosen_attributes[$taxonomy] = explode(',', $_GET[$name] );
	    		
	    endforeach;    	
    endif;

}

/**
 * Layered Nav post filter
 */
add_filter('loop_shop_post_in', 'woocommerce_layered_nav_query');

function woocommerce_layered_nav_query( $filtered_posts ) {
	
	global $_chosen_attributes, $wpdb, $woocommerce;
	
	if (sizeof($_chosen_attributes)>0) :
		
		$matched_products = array();
		$filtered = false;
		
		foreach ($_chosen_attributes as $attribute => $values) :
			if (sizeof($values)>0) :
				foreach ($values as $value) :
					
					$posts = get_objects_in_term( $value, $attribute );
					if (!is_wp_error($posts) && (sizeof($matched_products)>0 || $filtered)) :
						$matched_products = array_intersect($posts, $matched_products);
					elseif (!is_wp_error($posts)) :
						$matched_products = $posts;
					endif;
					
					$filtered = true;
					
				endforeach;
			endif;
		endforeach;
		
		if ($filtered) :
			
			$woocommerce->query->layered_nav_post__in = $matched_products;
			$woocommerce->query->layered_nav_post__in[] = 0;
			
			if (sizeof($filtered_posts)==0) :
				$filtered_posts = $matched_products;
				$filtered_posts[] = 0;
			else :
				$filtered_posts = array_intersect($filtered_posts, $matched_products);
				$filtered_posts[] = 0;
			endif;
			
		endif;
	
	endif;

	return (array) $filtered_posts;
}

/**
 * Layered Nav Widget
 */
class WooCommerce_Widget_Layered_Nav extends WP_Widget {
	
	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;
	
	/** constructor */
	function WooCommerce_Widget_Layered_Nav() {
		
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_layered_nav';
		$this->woo_widget_description = __( 'Shows a custom attribute in a widget which lets you narrow down the list of products when viewing product categories.', 'woothemes' );
		$this->woo_widget_idbase = 'woocommerce_layered_nav';
		$this->woo_widget_name = __('WooCommerce Layered Nav', 'woothemes' );
		
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );
		
		/* Create the widget. */
		$this->WP_Widget('layered_nav', $this->woo_widget_name, $widget_ops);
	}

	/** @see WP_Widget */
	function widget( $args, $instance ) {
		extract($args);
		
		if (!is_tax( 'product_cat' ) && !is_post_type_archive('product') && !is_tax( 'product_tag' )) return;
		
		global $_chosen_attributes, $wpdb, $woocommerce;
				
		$title = $instance['title'];
		$taxonomy = $woocommerce->attribute_taxonomy_name($instance['attribute']);
		
		if (!taxonomy_exists($taxonomy)) return;

		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		$args = array(
			'hide_empty' => '1'
		);
		$terms = get_terms( $taxonomy, $args );
		$count = count($terms);
		if($count > 0){

			$found = false;
			ob_start();

			echo $before_widget . $before_title . $title . $after_title;
			
			echo "<ul>";
			
			// Force found when option is selected
			if (array_key_exists($taxonomy, $_chosen_attributes)) $found = true;
			
			foreach ($terms as $term) {
			
				$_products_in_term = get_objects_in_term( $term->term_id, $taxonomy );
				
				$count = sizeof(array_intersect($_products_in_term, $woocommerce->query->filtered_product_ids));

				if ($count>0) $found = true;
				
				$option_is_set = (isset($_chosen_attributes[$taxonomy]) && in_array($term->term_id, $_chosen_attributes[$taxonomy]));
				
				if ($count==0 && !$option_is_set) continue;
				
				$class = '';
				
				$arg = 'filter_'.strtolower(sanitize_title($instance['attribute']));
				
				if (isset($_GET[ $arg ])) $current_filter = explode(',', $_GET[ $arg ]); else $current_filter = array();
				
				if (!is_array($current_filter)) $current_filter = array();
				
				if (!in_array($term->term_id, $current_filter)) $current_filter[] = $term->term_id;
				
				// Base Link decided by current page
				if (defined('SHOP_IS_ON_FRONT')) :
					$link = home_url();
				elseif (is_post_type_archive('product') || is_page( get_option('woocommerce_shop_page_id') )) :
					$link = get_post_type_archive_link('product');
				else :					
					$link = get_term_link( get_query_var('term'), get_query_var('taxonomy') );
				endif;
				
				// All current filters
				if ($_chosen_attributes) foreach ($_chosen_attributes as $name => $value) :
					if ($name!==$taxonomy) :
						$link = add_query_arg( strtolower(sanitize_title(str_replace('pa_', 'filter_', $name))), implode(',', $value), $link );
					endif;
				endforeach;
				
				// Min/Max
				if (isset($_GET['min_price'])) :
					$link = add_query_arg( 'min_price', $_GET['min_price'], $link );
				endif;
				if (isset($_GET['max_price'])) :
					$link = add_query_arg( 'max_price', $_GET['max_price'], $link );
				endif;
				
				// Current Filter = this widget
				if (isset( $_chosen_attributes[$taxonomy] ) && is_array($_chosen_attributes[$taxonomy]) && in_array($term->term_id, $_chosen_attributes[$taxonomy])) :
					$class = 'class="chosen"';
				else :
					$link = add_query_arg( $arg, implode(',', $current_filter), $link );
				endif;
				
				// Search Arg
				if (get_search_query()) :
					$link = add_query_arg( 's', get_search_query(), $link );
				endif;
				
				// Post Type Arg
				if (isset($_GET['post_type'])) :
					$link = add_query_arg( 'post_type', $_GET['post_type'], $link );
				endif;
				
				echo '<li '.$class.'>';
				
				if ($count>0 || $option_is_set) echo '<a href="'.$link.'">'; else echo '<span>';
				
				echo $term->name;
				
				if ($count>0 || $option_is_set) echo '</a>'; else echo '</span>';
				
				echo ' <small class="count">'.$count.'</small></li>';
				
			}
			
			echo "</ul>";
			
			echo $after_widget;
			
			if (!$found) :
				ob_clean();
				return;
			else :
				$widget = ob_get_clean();
				echo $widget;
			endif;
			
		}
	}
	
	/** @see WP_Widget->update */
	function update( $new_instance, $old_instance ) {
		global $woocommerce;
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = $woocommerce->attribute_label($new_instance['attribute']);
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['attribute'] = stripslashes($new_instance['attribute']);
		return $instance;
	}

	/** @see WP_Widget->form */
	function form( $instance ) {
		global $wpdb, $woocommerce;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
			
			<p><label for="<?php echo $this->get_field_id('attribute'); ?>"><?php _e('Attribute:', 'woothemes') ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id('attribute') ); ?>" name="<?php echo esc_attr( $this->get_field_name('attribute') ); ?>">
				<?php
				$attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
				if ( $attribute_taxonomies ) :
					foreach ($attribute_taxonomies as $tax) :
						if (taxonomy_exists( $woocommerce->attribute_taxonomy_name($tax->attribute_name))) :
							
							echo '<option value="'.$tax->attribute_name.'" ';
							if (isset($instance['attribute']) && $instance['attribute']==$tax->attribute_name) :
								echo 'selected="selected"';
							endif;
							echo '>'.$tax->attribute_name.'</option>';
							
						endif;
					endforeach;
				endif;
				?>
			</select>
		<?php
	}
} // class WooCommerce_Widget_Layered_Nav