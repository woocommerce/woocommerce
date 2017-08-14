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
				'title' => __( 'General Settings', 'woocommerce' ),
				'id'    => 'mz2l10u5f6',
			),
			'wc-settings-general' => array(
				'title' => __( 'General Settings', 'woocommerce' ),
				'id'    => 'mz2l10u5f6',
			),
			'wc-settings-products' => array(
				'title' => __( 'Product Settings', 'woocommerce' ),
				'id'    => 'lolkan4fxf',
			),
			'wc-settings-tax' => array(
				'title' => __( 'Tax Settings', 'woocommerce' ),
				'id'    => 'qp1v19dwrh',
			),
			'wc-settings-tax-standard' => array(
				'title' => __( 'Tax Rate Example', 'woocommerce' ),
				'id'    => '2p903vptwa',
			),
			'wc-settings-tax-reduced-rate' => array(
				'title' => __( 'Tax Rate Example', 'woocommerce' ),
				'id'    => '2p903vptwa',
			),
			'wc-settings-tax-zero-rate' => array(
				'title' => __( 'Tax Rate Example', 'woocommerce' ),
				'id'    => '2p903vptwa',
			),
			'wc-settings-shipping' => array(
				'title' => __( 'Shipping Zones', 'woocommerce' ),
				'id'    => '95yiocro6p',
			),
			'wc-settings-shipping-options' => array(
				'title' => __( 'Shipping Options', 'woocommerce' ),
				'id'    => '9c9008dxnr',
			),
			'wc-settings-shipping-classes' => array(
				'title' => __( 'Shipping Classes', 'woocommerce' ),
				'id'    => 'tpqg17aq99',
			),
			'wc-settings-checkout' => array(
				'title' => __( 'Checkout Settings', 'woocommerce' ),
				'id'    => '65yjv96z51',
			),
			'wc-settings-checkout-bacs' => array(
				'title' => __( 'Bank Transfer (BACS) Payment Method', 'woocommerce' ),
				'id'    => 'dh4piy3sek',
			),
			'wc-settings-checkout-cheque' => array(
				'title' => __( 'Check Payment Method', 'woocommerce' ),
				'id'    => 'u2m2kcakea',
			),
			'wc-settings-checkout-cod' => array(
				'title' => __( 'Cash on Delivery (COD) Payment Method', 'woocommerce' ),
				'id'    => '8hyli8wu5f',
			),
			'wc-settings-checkout-paypal' => array(
				'title' => __( 'PayPal Standard Method', 'woocommerce' ),
				'id'    => 'rbl7e7l4k2',
			),
			'wc-settings-checkout-paypalbraintree_cards' => array(
				'title' => __( 'PayPal by Braintree Payment Method', 'woocommerce' ),
				'id'    => 'oyksirgn40',
			),
			'wc-settings-checkout-stripe' => array(
				'title' => __( 'Stripe Payment Method', 'woocommerce' ),
				'id'    => 'mf975hx5de',
			),
			'wc-settings-account' => array(
				'title' => __( 'Account Settings', 'woocommerce' ),
				'id'    => '35mazq7il2',
			),
			'wc-settings-email' => array(
				'title' => __( 'Email Settings', 'woocommerce' ),
				'id'    => 'svcaftq4xv',
			),
			'wc-settings-api' => array(
				'title' => __( 'API Settings', 'woocommerce' ),
				'id'    => '1q0ny74vvq',
			),
			'product' => array(
				'title' => __( 'Creating Products', 'woocommerce' ),
				'id'    => 'fw0477t6wr',
			),
			'edit-product_cat' => array(
				'title' => __( 'Product Categories', 'woocommerce' ),
				'id'    => 'f0j5gzqigg',
			),
			'edit-product_tag' => array(
				'title' => __( 'Product Tags', 'woocommerce' ),
				'id'    => 'f0j5gzqigg',
			),
			'product_attributes' => array(
				'title' => __( 'Product Attributes', 'woocommerce' ),
				'id'    => 'f0j5gzqigg',
			),
			'wc-status' => array(
				'title' => __( 'System Status', 'woocommerce' ),
				'id'    => 'xdn733nnhi',
			),
			'wc-reports' => array(
				'title' => __( 'Reports', 'woocommerce' ),
				'id'    => '6aasex0w99',
			),
			'edit-shop_coupon' => array(
				'title' => __( 'Coupons', 'woocommerce' ),
				'id'    => 'gupd4h8sit',
			),
			'shop_coupon' => array(
				'title' => __( 'Coupons', 'woocommerce' ),
				'id'    => 'gupd4h8sit',
			),
			'edit-shop_order' => array(
				'title' => __( 'Managing Orders', 'woocommerce' ),
				'id'    => 'n8n0sa8hee',
			),
			'shop_order' => array(
				'title' => __( 'Managing Orders', 'woocommerce' ),
				'id'    => 'n8n0sa8hee',
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
				'id'        => 'woocommerce_guided_tour_tab',
				'title'     => __( 'Guided Tour', 'woocommerce' ),
				'content'   =>
					'<h2><a href="https://docs.woocommerce.com/document/woocommerce-guided-tour-videos/?utm_source=helptab&utm_medium=product&utm_content=videos&utm_campaign=woocommerceplugin">' . __( 'Guided Tour', 'woocommerce' ) . '</a> &ndash; ' . esc_html( $video_map[ $video_key ]['title'] ) . '</h2>' .
					'<script src="//fast.wistia.net/assets/external/E-v1.js" async></script>
					<div class="wistia_embed wistia_async_' . esc_attr( $video_map[ $video_key ]['id'] ) . ' videoFoam=true seo=false" style="width:640px;height:360px;">&nbsp;</div>
				',
			) );
		}

		$screen->add_help_tab( array(
			'id'        => 'woocommerce_support_tab',
			'title'     => __( 'Help &amp; Support', 'woocommerce' ),
			'content'   =>
				'<h2>' . __( 'Help &amp; Support', 'woocommerce' ) . '</h2>' .
				'<p>' . sprintf(
					__( 'Should you need help understanding, using, or extending WooCommerce, <a href="%s">please read our documentation</a>. You will find all kinds of resources including snippets, tutorials and much more.' , 'woocommerce' ),
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
