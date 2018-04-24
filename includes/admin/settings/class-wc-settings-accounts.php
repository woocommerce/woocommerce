<?php
/**
 * WooCommerce Account Settings.
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Accounts', false ) ) {
	return new WC_Settings_Accounts();
}

/**
 * WC_Settings_Accounts.
 */
class WC_Settings_Accounts extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'account';
		$this->label = __( 'Accounts &amp; Privacy', 'woocommerce' );
		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'woocommerce_' . $this->id . '_settings', array(
				array(
					'title' => '',
					'type'  => 'title',
					'id'    => 'account_registration_options',
				),
				array(
					'title'         => __( 'Guest checkout', 'woocommerce' ),
					'desc'          => __( 'Allow customers to place orders without an account.', 'woocommerce' ),
					'id'            => 'woocommerce_enable_guest_checkout',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'autoload'      => false,
				),
				array(
					'title'         => __( 'Login', 'woocommerce' ),
					'desc'          => __( 'Allow customers to log into an existing account during checkout', 'woocommerce' ),
					'id'            => 'woocommerce_enable_checkout_login_reminder',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
					'autoload'      => false,
				),
				array(
					'title'         => __( 'Account creation', 'woocommerce' ),
					'desc'          => __( 'Allow customers to create an account during checkout.', 'woocommerce' ),
					'id'            => 'woocommerce_enable_signup_and_login_from_checkout',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'autoload'      => false,
				),
				array(
					'desc'          => __( 'Allow customers to create an account on the "My account" page.', 'woocommerce' ),
					'id'            => 'woocommerce_enable_myaccount_registration',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => '',
					'autoload'      => false,
				),
				array(
					'desc'          => __( 'When creating an account, automatically generate a username from the customer\'s email address.', 'woocommerce' ),
					'id'            => 'woocommerce_registration_generate_username',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'checkboxgroup' => '',
					'autoload'      => false,
				),
				array(
					'desc'          => __( 'When creating an account, automatically generate an account password.', 'woocommerce' ),
					'id'            => 'woocommerce_registration_generate_password',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
					'autoload'      => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'account_registration_options',
				),
				array(
					'title' => __( 'Privacy policy', 'woocommerce' ),
					'type'  => 'title',
					'id'    => 'privacy_policy_options',
					'desc'  => __( 'This section controls the display of your website privacy policy. The privacy notices below will not show up unless a privacy page is first set.', 'woocommerce' ),
				),

				array(
					'title'    => __( 'Privacy page', 'woocommerce' ),
					'desc'     => __( 'Choose a page to act as your privacy policy.', 'woocommerce' ),
					'id'       => 'wp_page_for_privacy_policy',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Registration privacy policy', 'woocommerce' ),
					'desc_tip' => __( 'Optionally add some text about your store privacy policy to show on account registration forms.', 'woocommerce' ),
					'id'       => 'woocommerce_registration_privacy_policy_text',
					'default'  => __( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].', 'woocommerce' ),
					'type'     => 'textarea',
					'css'      => 'min-width: 50%; height: 75px;',
				),

				array(
					'title'    => __( 'Checkout privacy policy', 'woocommerce' ),
					'desc_tip' => __( 'Optionally add some text about your store privacy policy to show during checkout.', 'woocommerce' ),
					'id'       => 'woocommerce_checkout_privacy_policy_text',
					'default'  => __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'woocommerce' ),
					'type'     => 'textarea',
					'css'      => 'min-width: 50%; height: 75px;',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'privacy_policy_options',
				),
				array(
					'title' => __( 'Personal data handling', 'woocommerce' ),
					'desc'  => __( 'These tools let you clean up personal data when it\'s no longer needed for processing, or a user requests account erasure.', 'woocommerce' ),
					'type'  => 'title',
					'id'    => 'personal_data_handling',
				),
				array(
					'title'         => __( 'Erasure request handling', 'woocommerce' ),
					'desc'          => __( 'Remove personal data from orders', 'woocommerce' ),
					'desc_tip'      => __( 'When processing a request for erasure, should the order data be retained or removed?', 'woocommerce' ),
					'id'            => 'woocommerce_erasure_request_removes_order_data',
					'type'          => 'checkbox',
					'default'       => 'no',
					'checkboxgroup' => 'start',
					'autoload'      => false,
				),
				array(
					'desc'          => __( 'Remove access to downloads', 'woocommerce' ),
					'desc_tip'      => __( 'When processing a request for erasure, should access to downloadable files be revoked and download logs cleared?', 'woocommerce' ),
					'id'            => 'woocommerce_erasure_request_removes_download_data',
					'type'          => 'checkbox',
					'default'       => 'no',
					'checkboxgroup' => 'end',
					'autoload'      => false,
				),
				array(
					'title'       => __( 'Retain pending orders ', 'woocommerce' ),
					'desc_tip'    => __( 'Retain orders with this status for a specified duration before trashing them. Leave blank to retain these orders forever.', 'woocommerce' ),
					'id'          => 'woocommerce_trash_pending_orders',
					'type'        => 'relative_date_selector',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'default'     => '',
					'autoload'    => false,
				),
				array(
					'title'       => __( 'Retain failed orders', 'woocommerce' ),
					'desc_tip'    => __( 'Retain orders with this status for a specified duration before trashing them. Leave blank to retain these orders forever.', 'woocommerce' ),
					'id'          => 'woocommerce_trash_failed_orders',
					'type'        => 'relative_date_selector',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'default'     => '',
					'autoload'    => false,
				),
				array(
					'title'       => __( 'Retain cancelled orders', 'woocommerce' ),
					'desc_tip'    => __( 'Retain orders with this status for a specified duration before trashing them. Leave blank to retain these orders forever.', 'woocommerce' ),
					'id'          => 'woocommerce_trash_cancelled_orders',
					'type'        => 'relative_date_selector',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'default'     => '',
					'autoload'    => false,
				),
				array(
					'title'       => __( 'Retain completed orders', 'woocommerce' ),
					'desc_tip'    => __( 'Retain completed orders for a specified duration before anonymizing the personal data within them. Leave blank to retain these orders forever.', 'woocommerce' ),
					'id'          => 'woocommerce_anonymize_completed_orders',
					'type'        => 'relative_date_selector',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'default'     => array(
						'number' => '',
						'unit'   => 'years',
					),
					'autoload'    => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'personal_data_handling',
				),
			)
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}
}

return new WC_Settings_Accounts();
