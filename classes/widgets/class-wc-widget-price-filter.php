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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Widget_Price_Filter extends WP_Widget {

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
	function WC_Widget_Price_Filter() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'woocommerce widget_price_filter';
		$this->woo_widget_description = __( 'Shows a price filter slider in a widget which lets you narrow down the list of shown products when viewing product categories.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_price_filter';
		$this->woo_widget_name = __( 'WooCommerce Price Filter', 'woocommerce' );

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
		extract( $args );

		global $_chosen_attributes, $wpdb, $woocommerce, $wp_query, $wp;

		if (!is_tax( 'product_cat' ) && !is_post_type_archive('product') && !is_tax( 'product_tag' )) return; // Not on product page - return

		if ( sizeof( $woocommerce->query->unfiltered_product_ids ) == 0 ) return; // None shown - return

		$min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : '';
		$max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : '';

		wp_enqueue_script( 'wc-price-slider' );

		wp_localize_script( 'wc-price-slider', 'woocommerce_price_slider_params', array(
			'currency_symbol' 	=> get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos' ),
			'min_price'			=> $min_price,
			'max_price'			=> $max_price
		) );

		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		// Remember current filters/search
		$fields = '';

		if (get_search_query()) $fields = '<input type="hidden" name="s" value="'.get_search_query().'" />';
		if (isset($_GET['post_type'])) $fields .= '<input type="hidden" name="post_type" value="'.esc_attr( $_GET['post_type'] ).'" />';
		if (isset($_GET['product_cat'])) $fields .= '<input type="hidden" name="product_cat" value="'.esc_attr( $_GET['product_cat'] ).'" />';
		if (isset($_GET['product_tag'])) $fields .= '<input type="hidden" name="product_tag" value="'.esc_attr( $_GET['product_tag'] ).'" />';
		if (isset($_GET['orderby'])) $fields .= '<input type="hidden" name="orderby" value="' . esc_attr( $_GET['orderby'] ) . '" />';

		if ($_chosen_attributes) foreach ($_chosen_attributes as $attribute => $data) :

			$fields .= '<input type="hidden" name="'.esc_attr( str_replace('pa_', 'filter_', $attribute) ).'" value="'.esc_attr( implode(',', $data['terms']) ).'" />';
			if ($data['query_type']=='or') $fields .= '<input type="hidden" name="'.esc_attr( str_replace('pa_', 'query_type_', $attribute) ).'" value="or" />';

		endforeach;

		$min = $max = 0;
		$post_min = $post_max = '';

		if ( sizeof( $woocommerce->query->layered_nav_product_ids ) === 0 ) {
			$max = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE meta_key = \'%3$s\'
				', $wpdb->posts, $wpdb->postmeta, '_price' )
			) );
		} else {
			$max = ceil( $wpdb->get_var(
				$wpdb->prepare('
					SELECT max(meta_value + 0)
					FROM %1$s
					LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
					WHERE meta_key =\'%3$s\'
					AND (
						%1$s.ID IN (%4$s)
						OR (
							%1$s.post_parent IN (%4$s)
							AND %1$s.post_parent != 0
						)
					)
				', $wpdb->posts, $wpdb->postmeta, '_price', implode( ',', $woocommerce->query->layered_nav_product_ids )
			) ) );
		}

		if ( $min == $max ) return;

		echo $before_widget . $before_title . $title . $after_title;

		if ( get_option( 'permalink_structure' ) == '' )
			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		else
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );

		echo '<form method="get" action="' . $form_action . '">
			<div class="price_slider_wrapper">
				<div class="price_slider" style="display:none;"></div>
				<div class="price_slider_amount">
					<input type="text" id="min_price" name="min_price" value="'.esc_attr( $min_price ).'" data-min="'.esc_attr( $min ).'" placeholder="'.__('Min price', 'woocommerce' ).'" />
					<input type="text" id="max_price" name="max_price" value="'.esc_attr( $max_price ).'" data-max="'.esc_attr( $max ).'" placeholder="'.__( 'Max price', 'woocommerce' ).'" />
					<button type="submit" class="button">'.__( 'Filter', 'woocommerce' ).'</button>
					<div class="price_label" style="display:none;">
						'.__( 'Price:', 'woocommerce' ).' <span class="from"></span> &mdash; <span class="to"></span>
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
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = __( 'Filter by price', 'woocommerce' );
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
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'woocommerce' ) ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}
}