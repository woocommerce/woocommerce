<?php
/**
 * WooCommerce advanced settings
 *
 * @package  WooCommerce\Admin
 */

use Automattic\WooCommerce\Admin\Features\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Settings for API.
 */
if ( class_exists( 'WC_Settings_Advanced', false ) ) {
	return new WC_Settings_Advanced();
}

/**
 * WC_Settings_Advanced.
 */
class WC_Settings_Advanced extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'advanced';
		$this->label = __( 'Advanced', 'woocommerce' );

		parent::__construct();
		$this->notices();
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		$sections = array(
			''                => __( 'Page setup', 'woocommerce' ),
			'keys'            => __( 'REST API', 'woocommerce' ),
			'webhooks'        => __( 'Webhooks', 'woocommerce' ),
			'legacy_api'      => __( 'Legacy API', 'woocommerce' ),
			'woocommerce_com' => __( 'WooCommerce.com', 'woocommerce' ),
		);

		if ( Features::is_enabled( 'blueprint' ) ) {
			$sections['blueprint'] = __( 'Blueprint', 'woocommerce' );
		}

		return $sections;
	}

	/**
	 * Get settings for the default section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		$settings =
			array(
				array(
					'title' => __( 'Page setup', 'woocommerce' ),
					'desc'  => __( 'These pages need to be set so that WooCommerce knows where to send users to checkout.', 'woocommerce' ),
					'type'  => 'title',
					'id'    => 'advanced_page_options',
				),

				array(
					'title'    => __( 'Cart page', 'woocommerce' ),
					/* Translators: %s Page contents. */
					'desc'     => __( 'Page where shoppers review their shopping cart', 'woocommerce' ),
					'id'       => 'woocommerce_cart_page_id',
					'type'     => 'single_select_page_with_search',
					'default'  => '',
					'class'    => 'wc-page-search',
					'css'      => 'min-width:300px;',
					'args'     => array(
						'exclude' =>
							array(
								wc_get_page_id( 'checkout' ),
								wc_get_page_id( 'myaccount' ),
							),
					),
					'desc_tip' => true,
					'autoload' => false,
				),

				array(
					'title'    => __( 'Checkout page', 'woocommerce' ),
					/* Translators: %s Page contents. */
					'desc'     => __( 'Page where shoppers go to finalize their purchase', 'woocommerce' ),
					'id'       => 'woocommerce_checkout_page_id',
					'type'     => 'single_select_page_with_search',
					'default'  => wc_get_page_id( 'checkout' ),
					'class'    => 'wc-page-search',
					'css'      => 'min-width:300px;',
					'args'     => array(
						'exclude' =>
							array(
								wc_get_page_id( 'cart' ),
								wc_get_page_id( 'myaccount' ),
							),
					),
					'desc_tip' => true,
					'autoload' => false,
				),

				array(
					'title'    => __( 'My account page', 'woocommerce' ),
					/* Translators: %s Page contents. */
					'desc'     => sprintf( __( 'Page contents: [%s]', 'woocommerce' ), apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) ),
					'id'       => 'woocommerce_myaccount_page_id',
					'type'     => 'single_select_page_with_search',
					'default'  => '',
					'class'    => 'wc-page-search',
					'css'      => 'min-width:300px;',
					'args'     => array(
						'exclude' =>
							array(
								wc_get_page_id( 'cart' ),
								wc_get_page_id( 'checkout' ),
							),
					),
					'desc_tip' => true,
					'autoload' => false,
				),

				array(
					'title'    => __( 'Terms and conditions', 'woocommerce' ),
					'desc'     => __( 'If you define a "Terms" page the customer will be asked if they accept them when checking out.', 'woocommerce' ),
					'id'       => 'woocommerce_terms_page_id',
					'default'  => '',
					'class'    => 'wc-page-search',
					'css'      => 'min-width:300px;',
					'type'     => 'single_select_page_with_search',
					'args'     => array( 'exclude' => wc_get_page_id( 'checkout' ) ),
					'desc_tip' => true,
					'autoload' => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'advanced_page_options',
				),

				array(
					'title' => '',
					'type'  => 'title',
					'id'    => 'checkout_process_options',
				),

				'force_ssl_checkout'   => array(
					'title'           => __( 'Secure checkout', 'woocommerce' ),
					'desc'            => __( 'Force secure checkout', 'woocommerce' ),
					'id'              => 'woocommerce_force_ssl_checkout',
					'default'         => 'no',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'start',
					'show_if_checked' => 'option',
					/* Translators: %s Docs URL. */
					'desc_tip'        => sprintf( __( 'Force SSL (HTTPS) on the checkout pages (<a href="%s" target="_blank">an SSL Certificate is required</a>).', 'woocommerce' ), 'https://woocommerce.com/document/ssl-and-https/#section-3' ),
				),

				'unforce_ssl_checkout' => array(
					'desc'            => __( 'Force HTTP when leaving the checkout', 'woocommerce' ),
					'id'              => 'woocommerce_unforce_ssl_checkout',
					'default'         => 'no',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'end',
					'show_if_checked' => 'yes',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'checkout_process_options',
				),

				array(
					'title' => __( 'Checkout endpoints', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Endpoints are appended to your page URLs to handle specific actions during the checkout process. They should be unique.', 'woocommerce' ),
					'id'    => 'checkout_endpoint_options',
				),

				array(
					'title'    => __( 'Pay', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "Checkout &rarr; Pay" page.', 'woocommerce' ),
					'id'       => 'woocommerce_checkout_pay_endpoint',
					'type'     => 'text',
					'default'  => 'order-pay',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Order received', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "Checkout &rarr; Order received" page.', 'woocommerce' ),
					'id'       => 'woocommerce_checkout_order_received_endpoint',
					'type'     => 'text',
					'default'  => 'order-received',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Add payment method', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "Checkout &rarr; Add payment method" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_add_payment_method_endpoint',
					'type'     => 'text',
					'default'  => 'add-payment-method',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Delete payment method', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the delete payment method page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_delete_payment_method_endpoint',
					'type'     => 'text',
					'default'  => 'delete-payment-method',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Set default payment method', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the setting a default payment method page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_set_default_payment_method_endpoint',
					'type'     => 'text',
					'default'  => 'set-default-payment-method',
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'checkout_endpoint_options',
				),

				array(
					'title' => __( 'Account endpoints', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Endpoints are appended to your page URLs to handle specific actions on the accounts pages. They should be unique and can be left blank to disable the endpoint.', 'woocommerce' ),
					'id'    => 'account_endpoint_options',
				),

				array(
					'title'    => __( 'Orders', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; Orders" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_orders_endpoint',
					'type'     => 'text',
					'default'  => 'orders',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'View order', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; View order" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_view_order_endpoint',
					'type'     => 'text',
					'default'  => 'view-order',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Downloads', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; Downloads" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_downloads_endpoint',
					'type'     => 'text',
					'default'  => 'downloads',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Edit account', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; Edit account" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_edit_account_endpoint',
					'type'     => 'text',
					'default'  => 'edit-account',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Addresses', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; Addresses" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_edit_address_endpoint',
					'type'     => 'text',
					'default'  => 'edit-address',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Payment methods', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; Payment methods" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_payment_methods_endpoint',
					'type'     => 'text',
					'default'  => 'payment-methods',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Lost password', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the "My account &rarr; Lost password" page.', 'woocommerce' ),
					'id'       => 'woocommerce_myaccount_lost_password_endpoint',
					'type'     => 'text',
					'default'  => 'lost-password',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Logout', 'woocommerce' ),
					'desc'     => __( 'Endpoint for the triggering logout. You can add this to your menus via a custom link: yoursite.com/?customer-logout=true', 'woocommerce' ),
					'id'       => 'woocommerce_logout_endpoint',
					'type'     => 'text',
					'default'  => 'customer-logout',
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'account_endpoint_options',
				),
			);

		$settings = apply_filters( 'woocommerce_settings_pages', $settings );

		if ( wc_site_is_https() ) {
			unset( $settings['unforce_ssl_checkout'], $settings['force_ssl_checkout'] );
		}

		return $settings;
	}

	/**
	 * Get settings for the WooCommerce.com section.
	 *
	 * @return array
	 */
	protected function get_settings_for_woocommerce_com_section() {
		$tracking_info_text = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://woocommerce.com/usage-tracking', esc_html__( 'WooCommerce.com Usage Tracking Documentation', 'woocommerce' ) );

		$settings =
			array(
				array(
					'title' => esc_html__( 'Usage Tracking', 'woocommerce' ),
					'type'  => 'title',
					'id'    => 'tracking_options',
					'desc'  => __( 'Gathering usage data allows us to tailor your store setup experience, offer more relevant content, and help make WooCommerce better for everyone.', 'woocommerce' ),
				),
				array(
					'title'         => __( 'Enable tracking', 'woocommerce' ),
					'desc'          => __( 'Allow usage of WooCommerce to be tracked', 'woocommerce' ),
					/* Translators: %s URL to tracking info screen. */
					'desc_tip'      => sprintf( esc_html__( 'To opt out, leave this box unticked. Your store remains untracked, and no data will be collected. Read about what usage data is tracked at: %s.', 'woocommerce' ), $tracking_info_text ),
					'id'            => 'woocommerce_allow_tracking',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'default'       => 'no',
					'autoload'      => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'tracking_options',
				),
				array(
					'title' => esc_html__( 'Marketplace suggestions', 'woocommerce' ),
					'type'  => 'title',
					'id'    => 'marketplace_suggestions',
					'desc'  => __( 'We show contextual suggestions for official extensions that may be helpful to your store.', 'woocommerce' ),
				),
				array(
					'title'         => __( 'Show Suggestions', 'woocommerce' ),
					'desc'          => __( 'Display suggestions within WooCommerce', 'woocommerce' ),
					'desc_tip'      => esc_html__( 'Leave this box unchecked if you do not want to pull suggested extensions from WooCommerce.com. You will see a static list of extensions instead.', 'woocommerce' ),
					'id'            => 'woocommerce_show_marketplace_suggestions',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'default'       => 'yes',
					'autoload'      => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'marketplace_suggestions',
				),
			);

		return apply_filters( 'woocommerce_com_integration_settings', $settings );
	}

	/**
	 * Get settings for the legacy API section.
	 *
	 * @return array
	 */
	protected function get_settings_for_legacy_api_section() {
		$legacy_api_setting_desc =
			'yes' === get_option( 'woocommerce_api_enabled' ) ?
			__( 'The legacy REST API is enabled', 'woocommerce' ) :
			__( 'The legacy REST API is NOT enabled', 'woocommerce' );

		$legacy_api_setting_tip =
			WC()->legacy_rest_api_is_available() ?
			__( 'ℹ️️ The WooCommerce Legacy REST API extension is installed and active.', 'woocommerce' ) :
			sprintf(
				/* translators: placeholders are URLs */
				__( '⚠️ The WooCommerce Legacy REST API has been moved to <a target=”_blank” href="%1$s">a dedicated extension</a>. <b><a target=”_blank” href="%2$s">Learn more about this change</a></b>', 'woocommerce' ),
				'https://wordpress.org/plugins/woocommerce-legacy-rest-api/',
				'https://developer.woocommerce.com/2023/10/03/the-legacy-rest-api-will-move-to-a-dedicated-extension-in-woocommerce-9-0/'
			);

		$settings =
			array(
				array(
					'title' => '',
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'legacy_api_options',
				),
				array(
					'title'    => __( 'Legacy API', 'woocommerce' ),
					'desc'     => $legacy_api_setting_desc,
					'id'       => 'woocommerce_api_enabled',
					'type'     => 'checkbox',
					'default'  => 'no',
					'disabled' => true,
					'desc_tip' => $legacy_api_setting_tip,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'legacy_api_options',
				),
			);

		return apply_filters( 'woocommerce_settings_rest_api', $settings );
	}

	/**
	 * Get settings for the Blueprint section.
	 *
	 * @return array
	 */
	protected function get_settings_for_blueprint_section() {
		$settings =
			array(
				array(
					'title' => esc_html__( 'Blueprint', 'woocommerce' ),
					'type'  => 'title',
				),
				array(
					'id'   => 'wc_settings_blueprint_slotfill',
					'type' => 'slotfill_placeholder',
				),
			);

		return $settings;
	}

	/**
	 * Form method.
	 *
	 * @deprecated 3.4.4
	 *
	 * @param  string $method Method name.
	 *
	 * @return string
	 */
	public function form_method( $method ) {
		return 'post';
	}

	/**
	 * Notices.
	 */
	private function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['section'] ) && 'webhooks' === $_GET['section'] ) {
			WC_Admin_Webhooks::notices();
		}
		if ( isset( $_GET['section'] ) && 'keys' === $_GET['section'] ) {
			WC_Admin_API_Keys::notices();
		}
		// phpcs:enable
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		if ( 'webhooks' === $current_section ) {
			WC_Admin_Webhooks::page_output();
		} elseif ( 'keys' === $current_section ) {
			WC_Admin_API_Keys::page_output();
		} else {
			parent::output();
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		global $current_section;

		if ( apply_filters( 'woocommerce_rest_api_valid_to_save', ! in_array( $current_section, array( 'keys', 'webhooks' ), true ) ) ) {
			// Prevent the T&Cs and checkout page from being set to the same page.
			if ( isset( $_POST['woocommerce_terms_page_id'], $_POST['woocommerce_checkout_page_id'] ) && $_POST['woocommerce_terms_page_id'] === $_POST['woocommerce_checkout_page_id'] ) {
				$_POST['woocommerce_terms_page_id'] = '';
			}

			// Prevent the Cart, checkout and my account page from being set to the same page.
			if ( isset( $_POST['woocommerce_cart_page_id'], $_POST['woocommerce_checkout_page_id'], $_POST['woocommerce_myaccount_page_id'] ) ) {
				if ( $_POST['woocommerce_cart_page_id'] === $_POST['woocommerce_checkout_page_id'] ) {
					$_POST['woocommerce_checkout_page_id'] = '';
				}
				if ( $_POST['woocommerce_cart_page_id'] === $_POST['woocommerce_myaccount_page_id'] ) {
					$_POST['woocommerce_myaccount_page_id'] = '';
				}
				if ( $_POST['woocommerce_checkout_page_id'] === $_POST['woocommerce_myaccount_page_id'] ) {
					$_POST['woocommerce_myaccount_page_id'] = '';
				}
			}

			$this->save_settings_for_current_section();
			$this->do_update_options_action();
		}
		// phpcs:enable
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Generic.Commenting.Todo.CommentFound
/**
 * WC_Settings_Rest_API class.
 *
 * @deprecated 3.4 in favour of WC_Settings_Advanced.
 */
class WC_Settings_Rest_API extends WC_Settings_Advanced {
}

return new WC_Settings_Advanced();
// phpcs:enable
