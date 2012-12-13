<?php
/**
 * Price Filter Widget and related functions
 *
 * Generates a range slider to filter products by price.
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.6.4
 * @extends 	WP_Widget
 */
class WooCommerce_Widget_Price_Filter extends WP_Widget {

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
	function WooCommerce_Widget_Price_Filter() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_price_filter';
		$this->woo_widget_description = __( 'Shows a price filter slider in a widget which lets you narrow down the list of shown products when viewing product categories.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_price_filter';
		$this->woo_widget_name = __('WooCommerce Price Filter', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('price_filter', $this->woo_widget_name, $widget_ops);
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

		global $_chosen_attributes, $wpdb, $woocommerce, $wp_query, $wp;

		if (!is_tax( 'product_cat' ) && !is_post_type_archive('product') && !is_tax( 'product_tag' )) return; // Not on product page - return

		if ( sizeof( $woocommerce->query->unfiltered_product_ids ) == 0 ) return; // None shown - return

		wp_enqueue_script( 'wc-price-slider' );

		wp_localize_script( 'wc-price-slider', 'woocommerce_price_slider_params', array(
			'currency_symbol' 	=> get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos' ),
			'min_price'			=> isset( $_SESSION['min_price'] ) ? $_SESSION['min_price'] : '',
			'max_price'			=> isset( $_SESSION['max_price'] ) ? $_SESSION['max_price'] : ''
		) );

		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		// Remember current filters/search
		$fields = '';

		if (get_search_query()) $fields = '<input type="hidden" name="s" value="'.get_search_query().'" />';
		if (isset($_GET['post_type'])) $fields .= '<input type="hidden" name="post_type" value="'.esc_attr( $_GET['post_type'] ).'" />';
		if (isset($_GET['product_cat'])) $fields .= '<input type="hidden" name="product_cat" value="'.esc_attr( $_GET['product_cat'] ).'" />';
		if (isset($_GET['product_tag'])) $fields .= '<input type="hidden" name="product_tag" value="'.esc_attr( $_GET['product_tag'] ).'" />';

		if ($_chosen_attributes) foreach ($_chosen_attributes as $attribute => $data) :

			$fields .= '<input type="hidden" name="'.esc_attr( str_replace('pa_', 'filter_', $attribute) ).'" value="'.esc_attr( implode(',', $data['terms']) ).'" />';
			if ($data['query_type']=='or') $fields .= '<input type="hidden" name="'.esc_attr( str_replace('pa_', 'query_type_', $attribute) ).'" value="or" />';

		endforeach;

		$min = $max = 0;
		$post_min = $post_max = '';

		if ( sizeof( $woocommerce->query->layered_nav_product_ids ) == 0 ) :

			$max = ceil($wpdb->get_var("SELECT max(meta_value + 0)
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id
			WHERE meta_key = '_price'"));

		else :

			$max = ceil($wpdb->get_var("SELECT max(meta_value + 0)
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id
			WHERE meta_key = '_price' AND (
				$wpdb->posts.ID IN (".implode(',', $woocommerce->query->layered_nav_product_ids).")
				OR (
					$wpdb->posts.post_parent IN (".implode(',', $woocommerce->query->layered_nav_product_ids).")
					AND $wpdb->posts.post_parent != 0
				)
			)"));

		endif;

		if ( $min == $max ) return;

		if (isset($_SESSION['min_price'])) $post_min = $_SESSION['min_price'];
		if (isset($_SESSION['max_price'])) $post_max = $_SESSION['max_price'];

		echo $before_widget . $before_title . $title . $after_title;

		if ( get_option( 'permalink_structure' ) == '' )
			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		else
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );

		echo '<form method="get" action="' . $form_action . '">
			<div class="price_slider_wrapper">
				<div class="price_slider" style="display:none;"></div>
				<div class="price_slider_amount">
					<input type="text" id="min_price" name="min_price" value="'.esc_attr( $post_min ).'" data-min="'.esc_attr( $min ).'" placeholder="'.__('Min price', 'woocommerce').'" />
					<input type="text" id="max_price" name="max_price" value="'.esc_attr( $post_max ).'" data-max="'.esc_attr( $max ).'" placeholder="'.__('Max price', 'woocommerce').'" />
					<button type="submit" class="button">'.__('Filter', 'woocommerce').'</button>
					<div class="price_label" style="display:none;">
						'.__('Price:', 'woocommerce').' <span class="from"></span> &mdash; <span class="to"></span>
					</div>
					'.$fields.'
					<div class="clear"></div>
				</div>
			</div>
		</form>';

		echo $after_widget;
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
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = __('Filter by price', 'woocommerce');
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
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
		global $wpdb;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}
}


/**
 * Price filter Init
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @return void
 */
function woocommerce_price_filter_init() {
	global $woocommerce;

	if ( is_active_widget( false, false, 'price_filter', true ) && ! is_admin() ) {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-price-slider', $woocommerce->plugin_url() . '/assets/js/frontend/price-slider' . $suffix . '.js', array( 'jquery-ui-slider' ), '1.6', true );

		unset( $_SESSION['min_price'] );
		unset( $_SESSION['max_price'] );

		if ( isset( $_GET['min_price'] ) )
			$_SESSION['min_price'] = $_GET['min_price'];

		if ( isset( $_GET['max_price'] ) )
			$_SESSION['max_price'] = $_GET['max_price'];

		add_filter( 'loop_shop_post_in', 'woocommerce_price_filter' );
	}
}

add_action( 'init', 'woocommerce_price_filter_init' );


/**
 * Price Filter post filter
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @param array $filtered_posts
 * @return array
 */
function woocommerce_price_filter($filtered_posts) {
    global $wpdb;

    if ( isset( $_GET['max_price'] ) && isset( $_GET['min_price'] ) ) {

        $matched_products = array();
        $min 	= floatval( $_GET['min_price'] );
        $max 	= floatval( $_GET['max_price'] );

        $matched_products_query = $wpdb->get_results( $wpdb->prepare("
        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
			INNER JOIN $wpdb->postmeta ON ID = post_id
			WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = %s AND meta_value BETWEEN %d AND %d
		", '_price', $min, $max ), OBJECT_K );

        if ( $matched_products_query ) {
            foreach ( $matched_products_query as $product ) {
                if ( $product->post_type == 'product' )
                    $matched_products[] = $product->ID;
                if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
                    $matched_products[] = $product->post_parent;
            }
        }

        // Filter the id's
        if ( sizeof( $filtered_posts ) == 0) {
            $filtered_posts = $matched_products;
            $filtered_posts[] = 0;
        } else {
            $filtered_posts = array_intersect( $filtered_posts, $matched_products );
            $filtered_posts[] = 0;
        }

    }

    return (array) $filtered_posts;
}