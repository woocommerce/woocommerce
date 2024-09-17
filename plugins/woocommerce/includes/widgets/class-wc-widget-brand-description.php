<?php

declare( strict_types = 1);

/**
 * Brand Description Widget
 *
 * When viewing a brand archive, show the current brands description + image
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @package WooCommerce\Widgets
 * @version 9.4.0
 */
class WC_Widget_Brand_Description extends WP_Widget {

	/**
	 * Widget class.
	 *
	 * @var string
	 */
	public $woo_widget_cssclass;

	/**
	 * Widget description.
	 *
	 * @var string
	 */
	public $woo_widget_description;

	/**
	 * Widget idbase.
	 *
	 * @var string
	 */
	public $woo_widget_idbase;

	/**
	 * Widget name.
	 *
	 * @var string
	 */
	public $woo_widget_name;

	/** Constructor */
	public function __construct() {

		/* Widget variable settings. */
		$this->woo_widget_name        = __( 'WooCommerce Brand Description', 'woocommerce' );
		$this->woo_widget_description = __( 'When viewing a brand archive, show the current brands description.', 'woocommerce' );
		$this->woo_widget_idbase      = 'wc_brands_brand_description';
		$this->woo_widget_cssclass    = 'widget_brand_description';

		/* Widget settings. */
		$widget_ops = array(
			'classname'   => $this->woo_widget_cssclass,
			'description' => $this->woo_widget_description,
		);

		/* Create the widget. */
		parent::__construct( $this->woo_widget_idbase, $this->woo_widget_name, $widget_ops );
	}

	/**
	 * Echoes the widget content.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		if ( ! is_tax( 'product_brand' ) ) {
			return;
		}

		if ( ! get_query_var( 'term' ) ) {
			return;
		}

		$thumbnail = '';
		$term      = get_term_by( 'slug', get_query_var( 'term' ), 'product_brand' );

		$thumbnail = wc_get_brand_thumbnail_url( $term->term_id, 'large' );

		echo $before_widget . $before_title . $term->name . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput

		wc_get_template(
			'widgets/brand-description.php',
			array(
				'thumbnail' => $thumbnail,
				'brand'     => $term,
			),
			'woocommerce',
			WC()->plugin_path() . '/templates/brands/'
		);

		echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Updates widget instance.
	 *
	 * @see WP_Widget->update
	 *
	 * @param array $new_instance New widget instance.
	 * @param array $old_instance Old widget instance.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance['title'] = wp_strip_all_tags( stripslashes( $new_instance['title'] ) );
		return $instance;
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'woocommerce' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : ''; ?>" />
			</p>
		<?php
	}
}
