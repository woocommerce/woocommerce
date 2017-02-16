<?php
/**
 * Add some content to the help tab
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Admin_Help', false ) ) :

/**
 * WC_Admin_Help Class.
 */
class WC_Admin_Help {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'add_tabs' ), 50 );
	}

	/**
	 * Add Contextual help tabs.
	 */
	public function add_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, wc_get_screen_ids() ) ) {
			return;
		}

		$video_map = array(
			'wc-settings' => array(
				'title' => __( 'General settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/mz2l10u5f6?videoFoam=true',
			),
			'wc-settings-general' => array(
				'title' => __( 'General settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/mz2l10u5f6?videoFoam=true',
			),
			'wc-settings-products' => array(
				'title' => __( 'Product settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/lolkan4fxf?videoFoam=true',
			),
			'wc-settings-tax' => array(
				'title' => __( 'Tax settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/qp1v19dwrh?videoFoam=true',
			),
			'wc-settings-shipping' => array(
				'title' => __( 'Shipping zones', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/95yiocro6p?videoFoam=true',
			),
			'wc-settings-shipping-options' => array(
				'title' => __( 'Shipping options', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/9c9008dxnr?videoFoam=true',
			),
			'wc-settings-shipping-classes' => array(
				'title' => __( 'Shipping classes', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/tpqg17aq99?videoFoam=true',
			),
			'wc-settings-checkout' => array(
				'title' => __( 'Checkout settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/65yjv96z51?videoFoam=true',
			),
			'wc-settings-checkout-bacs' => array(
				'title' => __( 'Bank transfer (BACS) payments', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/dh4piy3sek?videoFoam=true',
			),
			'wc-settings-checkout-cheque' => array(
				'title' => __( 'Check payments', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/u2m2kcakea?videoFoam=true',
			),
			'wc-settings-checkout-cod' => array(
				'title' => __( 'Cash on delivery', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/8hyli8wu5f?videoFoam=true',
			),
			'wc-settings-checkout-paypal' => array(
				'title' => __( 'PayPal Standard', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/rbl7e7l4k2?videoFoam=true',
			),
			'wc-settings-checkout-paypalbraintree_cards' => array(
				'title' => __( 'PayPal by Braintree', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/oyksirgn40?videoFoam=true',
			),
			'wc-settings-checkout-stripe' => array(
				'title' => __( 'Stripe', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/mf975hx5de?videoFoam=true',
			),
			'wc-settings-checkout-simplify_commerce' => array(
				'title' => __( 'Simplify Commerce', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/jdfzjiiw61?videoFoam=true',
			),
			'wc-settings-account' => array(
				'title' => __( 'Account settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/35mazq7il2?videoFoam=true',
			),
			'wc-settings-email' => array(
				'title' => __( 'Email settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/svcaftq4xv?videoFoam=true',
			),
			'wc-settings-api' => array(
				'title' => __( 'Webhook settings', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/1q0ny74vvq?videoFoam=true',
			),
			'product' => array(
				'title' => __( 'Simple products', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/ziyjmd4kut?videoFoam=true',
			),
			'edit-product_cat' => array(
				'title' => __( 'Product categories', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/f0j5gzqigg?videoFoam=true',
			),
			'edit-product_tag' => array(
				'title' => __( 'Product categories, tags, shipping classes, and attributes', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/f0j5gzqigg?videoFoam=true',
			),
			'product_attributes' => array(
				'title' => __( 'Product categories, tags, shipping classes, and attributes', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/f0j5gzqigg?videoFoam=true',
			),
			'wc-status' => array(
				'title' => __( 'System status', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/xdn733nnhi?videoFoam=true',
			),
			'wc-reports' => array(
				'title' => __( 'Reports', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/6aasex0w99?videoFoam=true',
			),
			'edit-shop_coupon' => array(
				'title' => __( 'Coupons', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/gupd4h8sit?videoFoam=true',
			),
			'shop_coupon' => array(
				'title' => __( 'Coupons', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/gupd4h8sit?videoFoam=true',
			),
			'edit-shop_order' => array(
				'title' => __( 'Managing orders', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/n8n0sa8hee?videoFoam=true',
			),
			'shop_order' => array(
				'title' => __( 'Managing orders', 'woocommerce' ),
				'url'   => '//fast.wistia.net/embed/iframe/n8n0sa8hee?videoFoam=true',
			),
		);

		$page      = empty( $_GET['page'] ) ? '' : sanitize_title( $_GET['page'] );
		$tab       = empty( $_GET['tab'] ) ? '' : sanitize_title( $_GET['tab'] );
		$section   = empty( $_REQUEST['section'] ) ? '' : sanitize_title( $_REQUEST['section'] );
		$video_key = $page ? implode( '-', array_filter( array( $page, $tab, $section ) ) ) : $screen->id;

		// Fallback for sections
		if ( ! isset( $video_map[ $video_key ] ) ) {
			$video_key = $page ? implode( '-', array_filter( array( $page, $tab ) ) ) : $screen->id;
		}

		// Fallback for tabs
		if ( ! isset( $video_map[ $video_key ] ) ) {
			$video_key = $page ? $page : $screen->id;
		}

		if ( isset( $video_map[ $video_key ] ) ) {
			$screen->add_help_tab( array(
				'id'        => 'woocommerce_101_tab',
				'title'     => __( 'WooCommerce 101', 'woocommerce' ),
				'content'   =>
					'<h2><a href="https://docs.woocommerce.com/document/woocommerce-101-video-series/?utm_source=helptab&utm_medium=product&utm_content=videos&utm_campaign=woocommerceplugin">' . __( 'WooCommerce 101', 'woocommerce' ) . '</a> &ndash; ' . esc_html( $video_map[ $video_key ]['title'] ) . '</h2>' .
					'<iframe data-src="' . esc_url( $video_map[ $video_key ]['url'] ) . '" src="" allowtransparency="true" frameborder="0" scrolling="no" class="wistia_embed" name="wistia_embed" allowfullscreen mozallowfullscreen webkitallowfullscreen oallowfullscreen msallowfullscreen width="480" height="298"></iframe>',
			) );
		}

		$screen->add_help_tab( array(
			'id'        => 'woocommerce_support_tab',
			'title'     => __( 'Help &amp; Support', 'woocommerce' ),
			'content'   =>
				'<h2>' . __( 'Help &amp; Support', 'woocommerce' ) . '</h2>' .
				'<p>' . sprintf(
					__( 'Should you need help understanding, using, or extending WooCommerce, <a href="%s">splease read our documentation</a>. You will find all kinds of resources including snippets, tutorials and much more.' , 'woocommerce' ),
					'https://docs.woocommerce.com/documentation/plugins/woocommerce/?utm_source=helptab&utm_medium=product&utm_content=docs&utm_campaign=woocommerceplugin'
				) . '</p>' .
				'<p>' . sprintf(
					__( 'For further assistance with WooCommerce core you can use the <a href="%1$s">community forum</a>. If you need help with premium extensions sold by WooCommerce, please <a href="%2$s">use our helpdesk</a>.', 'woocommerce' ),
					'https://wordpress.org/support/plugin/woocommerce',
					'https://woocommerce.com/my-account/tickets/?utm_source=helptab&utm_medium=product&utm_content=tickets&utm_campaign=woocommerceplugin'
				) . '</p>' .
				'<p>' . __( 'Before asking for help we recommend checking the system status page to identify any problems with your configuration.', 'woocommerce' ) . '</p>' .
				'<p><a href="' . admin_url( 'admin.php?page=wc-status' ) . '" class="button button-primary">' . __( 'System status', 'woocommerce' ) . '</a> <a href="' . 'https://wordpress.org/support/plugin/woocommerce' . '" class="button">' . __( 'Community forum', 'woocommerce' ) . '</a> <a href="' . 'https://woocommerce.com/my-account/tickets/?utm_source=helptab&utm_medium=product&utm_content=tickets&utm_campaign=woocommerceplugin' . '" class="button">' . __( 'WooCommerce helpdesk', 'woocommerce' ) . '</a></p>',
		) );

		$screen->add_help_tab( array(
			'id'        => 'woocommerce_bugs_tab',
			'title'     => __( 'Found a bug?', 'woocommerce' ),
			'content'   =>
				'<h2>' . __( 'Found a bug?', 'woocommerce' ) . '</h2>' .
				'<p>' . sprintf( __( 'If you find a bug within WooCommerce core you can create a ticket via <a href="%1$s">Github issues</a>. Ensure you read the <a href="%2$s">contribution guide</a> prior to submitting your report. To help us solve your issue, please be as descriptive as possible and include your <a href="%3$s">system status report</a>.', 'woocommerce' ), 'https://github.com/woocommerce/woocommerce/issues?state=open', 'https://github.com/woocommerce/woocommerce/blob/master/.github/CONTRIBUTING.md', admin_url( 'admin.php?page=wc-status' ) ) . '</p>' .
				'<p><a href="' . 'https://github.com/woocommerce/woocommerce/issues?state=open' . '" class="button button-primary">' . __( 'Report a bug', 'woocommerce' ) . '</a> <a href="' . admin_url( 'admin.php?page=wc-status' ) . '" class="button">' . __( 'System status', 'woocommerce' ) . '</a></p>',

		) );

		$screen->add_help_tab( array(
			'id'        => 'woocommerce_education_tab',
			'title'     => __( 'Education', 'woocommerce' ),
			'content'   =>
				'<h2>' . __( 'Education', 'woocommerce' ) . '</h2>' .
				'<p>' . __( 'If you would like to learn about using WooCommerce from an expert, consider following a WooCommerce course offered by one of our educational partners.', 'woocommerce' ) . '</p>' .
				'<p><a href="' . 'https://woocommerce.com/educational-partners/?utm_source=helptab&utm_medium=product&utm_content=edupartners&utm_campaign=woocommerceplugin' . '" class="button button-primary">' . __( 'View education partners', 'woocommerce' ) . '</a></p>',
		) );

		$screen->add_help_tab( array(
			'id'        => 'woocommerce_onboard_tab',
			'title'     => __( 'Setup wizard', 'woocommerce' ),
			'content'   =>
				'<h2>' . __( 'Setup wizard', 'woocommerce' ) . '</h2>' .
				'<p>' . __( 'If you need to access the setup wizard again, please click on the button below.', 'woocommerce' ) . '</p>' .
				'<p><a href="' . admin_url( 'index.php?page=wc-setup' ) . '" class="button button-primary">' . __( 'Setup wizard', 'woocommerce' ) . '</a></p>',

		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'woocommerce' ) . '</strong></p>' .
			'<p><a href="' . 'https://woocommerce.com/?utm_source=helptab&utm_medium=product&utm_content=about&utm_campaign=woocommerceplugin' . '" target="_blank">' . __( 'About WooCommerce', 'woocommerce' ) . '</a></p>' .
			'<p><a href="' . 'https://wordpress.org/plugins/woocommerce/' . '" target="_blank">' . __( 'WordPress.org project', 'woocommerce' ) . '</a></p>' .
			'<p><a href="' . 'https://github.com/woocommerce/woocommerce' . '" target="_blank">' . __( 'Github project', 'woocommerce' ) . '</a></p>' .
			'<p><a href="' . 'https://woocommerce.com/product-category/themes/woocommerce/?utm_source=helptab&utm_medium=product&utm_content=wcthemes&utm_campaign=woocommerceplugin' . '" target="_blank">' . __( 'Official themes', 'woocommerce' ) . '</a></p>' .
			'<p><a href="' . 'https://woocommerce.com/product-category/woocommerce-extensions/?utm_source=helptab&utm_medium=product&utm_content=wcextensions&utm_campaign=woocommerceplugin' . '" target="_blank">' . __( 'Official extensions', 'woocommerce' ) . '</a></p>'
		);
	}
}

endif;

return new WC_Admin_Help();
