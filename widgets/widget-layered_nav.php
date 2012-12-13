<?php
/**
 * Layered Navigation Widget
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.6.4
 * @extends 	WP_Widget
 */
class WooCommerce_Widget_Layered_Nav extends WP_Widget {

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
	function WooCommerce_Widget_Layered_Nav() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_layered_nav';
		$this->woo_widget_description = __( 'Shows a custom attribute in a widget which lets you narrow down the list of products when viewing product categories.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_layered_nav';
		$this->woo_widget_name = __('WooCommerce Layered Nav', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('woocommerce_layered_nav', $this->woo_widget_name, $widget_ops);
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
		extract($args);

		global $_chosen_attributes, $woocommerce, $_attributes_array;

		if ( ! is_post_type_archive( 'product' ) && is_array( $_attributes_array ) && ! is_tax( array_merge( $_attributes_array, array( 'product_cat', 'product_tag' ) ) ) )
			return;

		$current_term 	= ($_attributes_array && is_tax($_attributes_array)) ? get_queried_object()->term_id : '';
		$current_tax 	= ($_attributes_array && is_tax($_attributes_array)) ? get_queried_object()->taxonomy : '';

		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		$taxonomy 	= $woocommerce->attribute_taxonomy_name($instance['attribute']);
		$query_type = (isset($instance['query_type'])) ? $instance['query_type'] : 'and';
		$display_type = (isset($instance['display_type'])) ? $instance['display_type'] : 'list';

		if (!taxonomy_exists($taxonomy)) return;

		$args = array(
			'hide_empty' => '1'
		);
		$terms = get_terms( $taxonomy, $args );

		$count = count($terms);

		if($count > 0){

			$found = false;
			ob_start();

			echo $before_widget . $before_title . $title . $after_title;

			// Force found when option is selected - do not force found on taxonomy attributes
			if ( !$_attributes_array || !is_tax($_attributes_array) )
				if (is_array($_chosen_attributes) && array_key_exists($taxonomy, $_chosen_attributes)) $found = true;

			if ($display_type=='dropdown') {

				// skip when viewing the taxonomy
				if ( $current_tax && $taxonomy == $current_tax ) {

					$found = false;

				} else {

					$taxonomy_filter = str_replace('pa_', '', $taxonomy);

					$found = true;

					echo '<select id="dropdown_layered_nav_'.$taxonomy_filter.'">';

					echo '<option value="">'. sprintf( __('Any %s', 'woocommerce'), $woocommerce->attribute_label( $taxonomy ) ) .'</option>';

					foreach ($terms as $term) {

						// If on a term page, skip that term in widget list
						if( $term->term_id == $current_term ) continue;

						// Get count based on current view - uses transients
						$transient_name = 'wc_ln_count_' . md5( sanitize_key($taxonomy) . sanitize_key( $term->term_id ) );

						if ( false === ( $_products_in_term = get_transient( $transient_name ) ) ) {

							$_products_in_term = get_objects_in_term( $term->term_id, $taxonomy );

							set_transient( $transient_name, $_products_in_term );
						}

						$option_is_set = (isset($_chosen_attributes[$taxonomy]) && in_array($term->term_id, $_chosen_attributes[$taxonomy]['terms']));

						// If this is an AND query, only show options with count > 0
						if ($query_type=='and') {

							$count = sizeof(array_intersect($_products_in_term, $woocommerce->query->filtered_product_ids));

							if ($count>0) $found = true;

							if ($count==0 && !$option_is_set) continue;

						// If this is an OR query, show all options so search can be expanded
						} else {

							$count = sizeof(array_intersect($_products_in_term, $woocommerce->query->unfiltered_product_ids));

							if ($count>0) $found = true;

						}

						echo '<option value="'.$term->term_id.'" '.selected( (isset($_GET['filter_'.$taxonomy_filter])) ? $_GET['filter_'.$taxonomy_filter] : '' , $term->term_id, false).'>'.$term->name.'</option>';
					}

					echo '</select>';

					$woocommerce->add_inline_js("

						jQuery('#dropdown_layered_nav_$taxonomy_filter').change(function(){

							location.href = '".add_query_arg('filtering', '1', remove_query_arg('filter_' . $taxonomy_filter))."&filter_$taxonomy_filter=' + jQuery('#dropdown_layered_nav_$taxonomy_filter').val();

						});

					");

				}

			} else {

				// List display
				echo "<ul>";

				foreach ($terms as $term) {

					// Get count based on current view - uses transients
					$transient_name = 'wc_ln_count_' . md5( sanitize_key($taxonomy) . sanitize_key( $term->term_id ) );

					if ( false === ( $_products_in_term = get_transient( $transient_name ) ) ) {

						$_products_in_term = get_objects_in_term( $term->term_id, $taxonomy );

						set_transient( $transient_name, $_products_in_term );
					}

					$option_is_set = (isset($_chosen_attributes[$taxonomy]) && in_array($term->term_id, $_chosen_attributes[$taxonomy]['terms'])) ;

					// If this is an AND query, only show options with count > 0
					if ($query_type=='and') {

						$count = sizeof(array_intersect($_products_in_term, $woocommerce->query->filtered_product_ids));

						// skip the term for the current archive
						if ( $current_term == $term->term_id ) continue;

						if ($count>0 && $current_term !== $term->term_id ) $found = true;

						if ($count==0 && !$option_is_set) continue;

					// If this is an OR query, show all options so search can be expanded
					} else {

						// skip the term for the current archive
						if ( $current_term == $term->term_id ) continue;

						$count = sizeof(array_intersect($_products_in_term, $woocommerce->query->unfiltered_product_ids));

						if ($count>0) $found = true;

					}

					$class = '';

					$arg = 'filter_'.strtolower(sanitize_title($instance['attribute']));

					if (isset($_GET[ $arg ])) $current_filter = explode(',', $_GET[ $arg ]); else $current_filter = array();

					if (!is_array($current_filter)) $current_filter = array();

					if (!in_array($term->term_id, $current_filter)) $current_filter[] = $term->term_id;

					// Base Link decided by current page
					if (defined('SHOP_IS_ON_FRONT')) :
						$link = home_url();
					elseif (is_post_type_archive('product') || is_page( woocommerce_get_page_id('shop') )) :
						$link = get_post_type_archive_link('product');
					else :
						$link = get_term_link( get_query_var('term'), get_query_var('taxonomy') );
					endif;

					// All current filters
					if ($_chosen_attributes) :
						foreach ($_chosen_attributes as $name => $data) :
							if ( $name!==$taxonomy ) :

								//exclude query arg for current term archive term
								while(in_array($current_term, $data['terms'])) {
									$key = array_search($current_term, $data);
									unset($data['terms'][$key]);
								}

								if(!empty($data['terms'])){
									$link = add_query_arg( strtolower(sanitize_title(str_replace('pa_', 'filter_', $name))), implode(',', $data['terms']), $link );
								}

								if ($data['query_type']=='or') $link = add_query_arg( strtolower(sanitize_title(str_replace('pa_', 'query_type_', $name))), 'or', $link );
							endif;
						endforeach;
					endif;

					// Min/Max
					if (isset($_GET['min_price'])) :
						$link = add_query_arg( 'min_price', $_GET['min_price'], $link );
					endif;
					if (isset($_GET['max_price'])) :
						$link = add_query_arg( 'max_price', $_GET['max_price'], $link );
					endif;

					// Current Filter = this widget
					if (isset( $_chosen_attributes[$taxonomy] ) && is_array($_chosen_attributes[$taxonomy]['terms']) && in_array($term->term_id, $_chosen_attributes[$taxonomy]['terms'])) :
						$class = 'class="chosen"';

						// Remove this term is $current_filter has more than 1 term filtered
						if (sizeof($current_filter)>1) :
							$current_filter_without_this = array_diff($current_filter, array($term->term_id));
							$link = add_query_arg( $arg, implode(',', $current_filter_without_this), $link );
						endif;

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

					// Query type Arg
					if ($query_type=='or' && !( sizeof($current_filter) == 1 && isset( $_chosen_attributes[$taxonomy]['terms'] ) && is_array($_chosen_attributes[$taxonomy]['terms']) && in_array($term->term_id, $_chosen_attributes[$taxonomy]['terms']) )) :
						$link = add_query_arg( 'query_type_'.strtolower(sanitize_title($instance['attribute'])), 'or', $link );
					endif;

					echo '<li '.$class.'>';

					if ($count>0 || $option_is_set) echo '<a href="'.$link.'">'; else echo '<span>';

					echo $term->name;

					if ($count>0 || $option_is_set) echo '</a>'; else echo '</span>';

					echo ' <small class="count">'.$count.'</small></li>';

				}

				echo "</ul>";

			} // End display type conditional

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
		global $woocommerce;
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = $woocommerce->attribute_label($new_instance['attribute']);
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['attribute'] = stripslashes($new_instance['attribute']);
		$instance['query_type'] = stripslashes($new_instance['query_type']);
		$instance['display_type'] = stripslashes($new_instance['display_type']);
		return $instance;
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
		global $woocommerce;

		if (!isset($instance['query_type'])) $instance['query_type'] = 'and';
		if (!isset($instance['display_type'])) $instance['display_type'] = 'list';
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>

			<p><label for="<?php echo $this->get_field_id('attribute'); ?>"><?php _e('Attribute:', 'woocommerce') ?></label>
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
			</select></p>

			<p><label for="<?php echo $this->get_field_id('display_type'); ?>"><?php _e('Display Type:', 'woocommerce') ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id('display_type') ); ?>" name="<?php echo esc_attr( $this->get_field_name('display_type') ); ?>">
				<option value="list" <?php selected($instance['display_type'], 'list'); ?>><?php _e('List', 'woocommerce'); ?></option>
				<option value="dropdown" <?php selected($instance['display_type'], 'dropdown'); ?>><?php _e('Dropdown', 'woocommerce'); ?></option>
			</select></p>

			<p><label for="<?php echo $this->get_field_id('query_type'); ?>"><?php _e('Query Type:', 'woocommerce') ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id('query_type') ); ?>" name="<?php echo esc_attr( $this->get_field_name('query_type') ); ?>">
				<option value="and" <?php selected($instance['query_type'], 'and'); ?>><?php _e('AND', 'woocommerce'); ?></option>
				<option value="or" <?php selected($instance['query_type'], 'or'); ?>><?php _e('OR', 'woocommerce'); ?></option>
			</select></p>
		<?php
	}
}

/**
 * Layered Nav Init
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @return void
 */
function woocommerce_layered_nav_init( ) {

	if ( is_active_widget( false, false, 'woocommerce_layered_nav', true ) && ! is_admin() ) {

		global $_chosen_attributes, $woocommerce, $_attributes_array;

		$_chosen_attributes = array();
		$_attributes_array = array();

		$attribute_taxonomies = $woocommerce->attribute_taxonomies;
		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {

		    	$attribute = strtolower(sanitize_title($tax->attribute_name));
		    	$taxonomy = $woocommerce->attribute_taxonomy_name($attribute);

				// create an array of product attribute taxonomies
				$_attributes_array[] = $taxonomy;

		    	$name = 'filter_' . $attribute;
		    	$query_type_name = 'query_type_' . $attribute;

		    	if ( ! empty( $_GET[$name] ) && taxonomy_exists( $taxonomy ) ) {

		    		$_chosen_attributes[$taxonomy]['terms'] = explode( ',', $_GET[ $name ] );

		    		if ( ! empty( $_GET[$query_type_name] ) && $_GET[ $query_type_name ] == 'or' )
		    			$_chosen_attributes[$taxonomy]['query_type'] = 'or';
		    		else
		    			$_chosen_attributes[$taxonomy]['query_type'] = 'and';

				}
			}
	    }

	    add_filter('loop_shop_post_in', 'woocommerce_layered_nav_query');
    }
}

add_action( 'init', 'woocommerce_layered_nav_init', 1 );


/**
 * Layered Nav post filter
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @param array $filtered_posts
 * @return array
 */
function woocommerce_layered_nav_query( $filtered_posts ) {
	global $_chosen_attributes, $woocommerce, $wp_query;

	if (sizeof($_chosen_attributes)>0) :

		$matched_products = array();
		$filtered_attribute = false;

		foreach ($_chosen_attributes as $attribute => $data) :

			$matched_products_from_attribute = array();
			$filtered = false;

			if (sizeof($data['terms'])>0) :
				foreach ($data['terms'] as $value) :

					$posts = get_posts(
						array(
							'post_type' 	=> 'product',
							'numberposts' 	=> -1,
							'post_status' 	=> 'publish',
							'fields' 		=> 'ids',
							'no_found_rows' => true,
							'tax_query' => array(
								array(
									'taxonomy' 	=> $attribute,
									'terms' 	=> $value,
									'field' 	=> 'id'
								)
							)
						)
					);

					// AND or OR
					if ($data['query_type']=='or') :

						if (!is_wp_error($posts) && (sizeof($matched_products_from_attribute)>0 || $filtered)) :
							$matched_products_from_attribute = array_merge($posts, $matched_products_from_attribute);
						elseif (!is_wp_error($posts)) :
							$matched_products_from_attribute = $posts;
						endif;

					else :

						if (!is_wp_error($posts) && (sizeof($matched_products_from_attribute)>0 || $filtered)) :
							$matched_products_from_attribute = array_intersect($posts, $matched_products_from_attribute);
						elseif (!is_wp_error($posts)) :
							$matched_products_from_attribute = $posts;
						endif;

					endif;

					$filtered = true;

				endforeach;
			endif;

			if (sizeof($matched_products)>0 || $filtered_attribute) :
				$matched_products = array_intersect($matched_products_from_attribute, $matched_products);
			else :
				$matched_products = $matched_products_from_attribute;
			endif;

			$filtered_attribute = true;

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