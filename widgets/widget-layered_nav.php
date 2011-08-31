<?php
/**
 * Layered Navigation Widget
 * 
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
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

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract($args);
		
		if (!is_tax( 'product_cat' ) && !is_post_type_archive('product') && !is_tax( 'product_tag' )) return;
		
		global $_chosen_attributes, $wpdb, $woocommerce_query;
				
		$title = $instance['title'];
		$taxonomy = 'product_attribute_'.strtolower(sanitize_title($instance['attribute']));
		
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
			
			foreach ($terms as $term) {
			
				$_products_in_term = get_objects_in_term( $term->term_id, $taxonomy );
				
				$count = sizeof(array_intersect($_products_in_term, $woocommerce_query['filtered_product_ids']));
				
				if ($count>0) $found = true;
				
				$class = '';
				
				$arg = 'filter_'.strtolower(sanitize_title($instance['attribute']));
				
				if (isset($_GET[ $arg ])) $current_filter = explode(',', $_GET[ $arg ]); else $current_filter = array();
				
				if (!is_array($current_filter)) $current_filter = array();
				
				if (!in_array($term->term_id, $current_filter)) $current_filter[] = $term->term_id;
				
				// Base Link decided by current page
				if (defined('SHOP_IS_ON_FRONT')) :
					$link = '';
				elseif (is_post_type_archive('product') || is_page( get_option('woocommerce_shop_page_id') )) :
					$link = get_post_type_archive_link('product');
				else :					
					$link = get_term_link( get_query_var('term'), get_query_var('taxonomy') );
				endif;
				
				// All current filters
				if ($_chosen_attributes) foreach ($_chosen_attributes as $name => $value) :
					if ($name!==$taxonomy) :
						$link = add_query_arg( strtolower(sanitize_title(str_replace('product_attribute_', 'filter_', $name))), implode(',', $value), $link );
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
				
				if ($count>0) echo '<a href="'.$link.'">'; else echo '<span>';
				
				echo $term->name;
				
				if ($count>0) echo '</a>'; else echo '</span>';
				
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
	
	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = ucwords($new_instance['attribute']);
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['attribute'] = stripslashes($new_instance['attribute']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		global $wpdb;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woothemes') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
			
			<p><label for="<?php echo $this->get_field_id('attribute'); ?>"><?php _e('Attribute:', 'woothemes') ?></label>
			<select id="<?php echo $this->get_field_id('attribute'); ?>" name="<?php echo $this->get_field_name('attribute'); ?>">
				<?php
				$attribute_taxonomies = woocommerce::$attribute_taxonomies;
				if ( $attribute_taxonomies ) :
					foreach ($attribute_taxonomies as $tax) :
						if (taxonomy_exists('product_attribute_'.strtolower(sanitize_title($tax->attribute_name)))) :
							
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