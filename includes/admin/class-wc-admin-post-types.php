<?php
/**
 * Post Types Admin
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_Post_Types' ) ) :

/**
 * WC_Admin_Post_Types Class
 */
class WC_Admin_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'include_post_type_handlers' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
	}

	/**
	 * Conditonally load classes and functions only needed when viewing a post type.
	 */
	public function include_post_type_handlers() {
		global $pagenow;

		include_once( 'post-types/writepanels/writepanels-init.php' );

		include( 'post-types/class-wc-admin-cpt-product.php' );

		if ( ! function_exists( 'duplicate_post_plugin_activation' ) )
			include( 'class-wc-admin-duplicate-product.php' );
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['product'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Product updated. <a href="%s">View Product</a>', 'woocommerce' ), esc_url( get_permalink($post_ID) ) ),
			2 => __( 'Custom field updated.', 'woocommerce' ),
			3 => __( 'Custom field deleted.', 'woocommerce' ),
			4 => __( 'Product updated.', 'woocommerce' ),
			5 => isset($_GET['revision']) ? sprintf( __( 'Product restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Product published. <a href="%s">View Product</a>', 'woocommerce' ), esc_url( get_permalink($post_ID) ) ),
			7 => __( 'Product saved.', 'woocommerce' ),
			8 => sprintf( __( 'Product submitted. <a target="_blank" href="%s">Preview Product</a>', 'woocommerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( 'Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Product</a>', 'woocommerce' ),
			  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __( 'Product draft updated. <a target="_blank" href="%s">Preview Product</a>', 'woocommerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

		$messages['shop_order'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Order updated.', 'woocommerce' ),
			2 => __( 'Custom field updated.', 'woocommerce' ),
			3 => __( 'Custom field deleted.', 'woocommerce' ),
			4 => __( 'Order updated.', 'woocommerce' ),
			5 => isset($_GET['revision']) ? sprintf( __( 'Order restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Order updated.', 'woocommerce' ),
			7 => __( 'Order saved.', 'woocommerce' ),
			8 => __( 'Order submitted.', 'woocommerce' ),
			9 => sprintf( __( 'Order scheduled for: <strong>%1$s</strong>.', 'woocommerce' ),
			  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Order draft updated.', 'woocommerce' )
		);

		$messages['shop_coupon'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Coupon updated.', 'woocommerce' ),
			2 => __( 'Custom field updated.', 'woocommerce' ),
			3 => __( 'Custom field deleted.', 'woocommerce' ),
			4 => __( 'Coupon updated.', 'woocommerce' ),
			5 => isset($_GET['revision']) ? sprintf( __( 'Coupon restored to revision from %s', 'woocommerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Coupon updated.', 'woocommerce' ),
			7 => __( 'Coupon saved.', 'woocommerce' ),
			8 => __( 'Coupon submitted.', 'woocommerce' ),
			9 => sprintf( __( 'Coupon scheduled for: <strong>%1$s</strong>.', 'woocommerce' ),
			  date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Coupon draft updated.', 'woocommerce' )
		);

		return $messages;
	}


}

endif;

return new WC_Admin_Post_Types();