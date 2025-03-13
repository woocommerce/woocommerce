<?php
/**
 * WooCommerce Account Settings.
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Accounts', false ) ) {
	return new WC_Settings_Accounts();
}

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Admin\Features\Features;

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
	 * Setting page icon.
	 *
	 * @var string
	 */
	public $icon = 'people';

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {

		$erasure_text = esc_html__( 'account erasure request', 'woocommerce' );
		$privacy_text = esc_html__( 'privacy page', 'woocommerce' );
		if ( current_user_can( 'manage_privacy_options' ) ) {
			if ( version_compare( get_bloginfo( 'version' ), '5.3', '<' ) ) {
				$erasure_text = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ), $erasure_text );
			} else {
				$erasure_text = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'erase-personal-data.php' ) ), $erasure_text );
			}
			$privacy_text = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-privacy.php' ) ), $privacy_text );
		}

		$account_settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'account_registration_options',
			),
			array(
				'title'         => __( 'Checkout', 'woocommerce' ),
				'desc'          => __( 'Enable guest checkout (recommended)', 'woocommerce' ),
				'desc_tip'      => __( 'Allows customers to checkout without an account.', 'woocommerce' ),
				'id'            => 'woocommerce_enable_guest_checkout',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => false,
			),
			array(
				'title'         => __( 'Login', 'woocommerce' ),
				'desc'          => __( 'Enable log-in during checkout', 'woocommerce' ),
				'id'            => 'woocommerce_enable_checkout_login_reminder',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => false,
			),
			array(
				'title'             => __( 'Account creation', 'woocommerce' ),
				'desc'              => __( 'After checkout (recommended)', 'woocommerce' ),
				'desc_tip'          => sprintf(
					/* Translators: %1$s and %2$s are opening and closing <a> tags respectively. */
					__( 'Customers can create an account after their order is placed. Customize messaging %1$shere%2$s.', 'woocommerce' ),
					'<a target="_blank" class="delayed-account-creation-customize-link" href="' . esc_url( admin_url( 'site-editor.php?postId=woocommerce%2Fwoocommerce%2F%2Forder-confirmation&postType=wp_template&canvas=edit' ) ) . '">',
					'</a>'
				),
				'id'                => 'woocommerce_enable_delayed_account_creation',
				'default'           => 'no',
				'type'              => 'checkbox',
				'checkboxgroup'     => 'start',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled-tooltip' => __( 'Enable guest checkout to use this feature.', 'woocommerce' ),
				),
				'legend'            => __( 'Allow customers to create an account', 'woocommerce' ),
			),
			array(
				'title'         => __( 'Account creation', 'woocommerce' ),
				'desc'          => __( 'During checkout', 'woocommerce' ),
				'desc_tip'      => __( 'Customers can create an account before placing their order.', 'woocommerce' ),
				'id'            => 'woocommerce_enable_signup_and_login_from_checkout',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => false,
			),
			array(
				'title'         => __( 'Account creation', 'woocommerce' ),
				'desc'          => __( 'On "My account" page', 'woocommerce' ),
				'id'            => 'woocommerce_enable_myaccount_registration',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => false,
			),
			array(
				'title'             => __( 'Account creation options', 'woocommerce' ),
				'desc'              => __( 'Send password setup link (recommended)', 'woocommerce' ),
				'desc_tip'          => __( 'New users receive an email to set up their password.', 'woocommerce' ),
				'id'                => 'woocommerce_registration_generate_password',
				'default'           => 'yes',
				'type'              => 'checkbox',
				'checkboxgroup'     => 'start',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled-tooltip' => __( 'Enable an account creation method to use this feature.', 'woocommerce' ),
				),
			),
			array(
				'title'             => __( 'Account creation options', 'woocommerce' ),
				'desc'              => __( 'Use email address as account login (recommended)', 'woocommerce' ),
				'desc_tip'          => __( 'If unchecked, customers will need to set a username during account creation.', 'woocommerce' ),
				'id'                => 'woocommerce_registration_generate_username',
				'default'           => 'yes',
				'type'              => 'checkbox',
				'checkboxgroup'     => 'end',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled-tooltip' => __( 'Enable an account creation method to use this feature.', 'woocommerce' ),
				),
			),
			array(
				'title'         => __( 'Account erasure requests', 'woocommerce' ),
				'desc'          => __( 'Remove personal data from orders on request', 'woocommerce' ),
				/* Translators: %s URL to erasure request screen. */
				'desc_tip'      => sprintf( esc_html__( 'When handling an %s, should personal data within orders be retained or removed?', 'woocommerce' ), $erasure_text ),
				'id'            => 'woocommerce_erasure_request_removes_order_data',
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => 'start',
				'autoload'      => false,
			),
			array(
				'desc'          => __( 'Remove access to downloads on request', 'woocommerce' ),
				/* Translators: %s URL to erasure request screen. */
				'desc_tip'      => sprintf( esc_html__( 'When handling an %s, should access to downloadable files be revoked and download logs cleared?', 'woocommerce' ), $erasure_text ),
				'id'            => 'woocommerce_erasure_request_removes_download_data',
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => '',
				'autoload'      => false,
			),
			array(
				'title'         => __( 'Personal data removal', 'woocommerce' ),
				'desc'          => __( 'Allow personal data to be removed in bulk from orders', 'woocommerce' ),
				'desc_tip'      => __( 'Adds an option to the orders screen for removing personal data in bulk. Note that removing personal data cannot be undone.', 'woocommerce' ),
				'id'            => 'woocommerce_allow_bulk_remove_personal_data',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'default'       => 'no',
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
				/* translators: %s: privacy page link. */
				'desc'  => sprintf( esc_html__( 'This section controls the display of your website privacy policy. The privacy notices below will not show up unless a %s is set.', 'woocommerce' ), $privacy_text ),
			),

			array(
				'title'    => __( 'Registration privacy policy', 'woocommerce' ),
				'desc_tip' => __( 'Optionally add some text about your store privacy policy to show on account registration forms.', 'woocommerce' ),
				'id'       => 'woocommerce_registration_privacy_policy_text',
				/* translators: %s privacy policy page name and link */
				'default'  => sprintf( __( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.', 'woocommerce' ), '[privacy_policy]' ),
				'type'     => 'textarea',
				'css'      => 'min-width: 50%; height: 75px;',
			),

			array(
				'title'    => __( 'Checkout privacy policy', 'woocommerce' ),
				'desc_tip' => __( 'Optionally add some text about your store privacy policy to show during checkout.', 'woocommerce' ),
				'id'       => 'woocommerce_checkout_privacy_policy_text',
				/* translators: %s privacy policy page name and link */
				'default'  => sprintf( __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.', 'woocommerce' ), '[privacy_policy]' ),
				'type'     => 'textarea',
				'css'      => 'min-width: 50%; height: 75px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'privacy_policy_options',
			),
			array(
				'title' => __( 'Personal data retention', 'woocommerce' ),
				'desc'  => __( 'Choose how long to retain personal data when it\'s no longer needed for processing. Leave the following options blank to retain this data indefinitely.', 'woocommerce' ),
				'type'  => 'title',
				'id'    => 'personal_data_retention',
			),
			array(
				'title'       => __( 'Retain inactive accounts ', 'woocommerce' ),
				'desc_tip'    => __( 'Inactive accounts are those which have not logged in, or placed an order, for the specified duration. They will be deleted. Any orders will be converted into guest orders.', 'woocommerce' ),
				'id'          => 'woocommerce_delete_inactive_accounts',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => array(
					'number' => '',
					'unit'   => 'months',
				),
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain pending orders ', 'woocommerce' ),
				'desc_tip'    => __( 'Pending orders are unpaid and may have been abandoned by the customer. They will be trashed after the specified duration.', 'woocommerce' ),
				'id'          => 'woocommerce_trash_pending_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => '',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain failed orders', 'woocommerce' ),
				'desc_tip'    => __( 'Failed orders are unpaid and may have been abandoned by the customer. They will be trashed after the specified duration.', 'woocommerce' ),
				'id'          => 'woocommerce_trash_failed_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => '',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain cancelled orders', 'woocommerce' ),
				'desc_tip'    => __( 'Cancelled orders are unpaid and may have been cancelled by the store owner or customer. They will be trashed after the specified duration.', 'woocommerce' ),
				'id'          => 'woocommerce_trash_cancelled_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => '',
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain refunded orders', 'woocommerce' ),
				'desc_tip'    => __( 'Retain refunded orders for a specified duration before anonymizing the personal data within them.', 'woocommerce' ),
				'id'          => 'woocommerce_anonymize_refunded_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => array(
					'number' => '',
					'unit'   => 'months',
				),
				'autoload'    => false,
			),
			array(
				'title'       => __( 'Retain completed orders', 'woocommerce' ),
				'desc_tip'    => __( 'Retain completed orders for a specified duration before anonymizing the personal data within them.', 'woocommerce' ),
				'id'          => 'woocommerce_anonymize_completed_orders',
				'type'        => 'relative_date_selector',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => array(
					'number' => '',
					'unit'   => 'months',
				),
				'autoload'    => false,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'personal_data_retention',
			),
		);

		// Feature requires a block theme. Re-order settings if not using a block theme.
		if ( ! wc_current_theme_is_fse_theme() ) {
			$account_settings = array_map(
				function ( $setting ) {
					if ( 'woocommerce_enable_signup_and_login_from_checkout' === $setting['id'] ) {
						$setting['checkboxgroup'] = 'start';
						$setting['legend']        = __( 'Allow customers to create an account', 'woocommerce' );
					}
					return $setting;
				},
				$account_settings
			);
			$account_settings = array_filter(
				$account_settings,
				function ( $setting ) {
					return 'woocommerce_enable_delayed_account_creation' !== $setting['id'];
				},
			);
		}

		// Change settings when using the block based checkout.
		if ( CartCheckoutUtils::is_checkout_block_default() ) {
			$account_settings = array_filter(
				$account_settings,
				function ( $setting ) {
					return 'woocommerce_registration_generate_username' !== $setting['id'];
				},
			);
			$account_settings = array_map(
				function ( $setting ) {
					if ( 'woocommerce_registration_generate_password' === $setting['id'] ) {
						unset( $setting['checkboxgroup'] );
					}
					return $setting;
				},
				$account_settings
			);
		} else {
			$account_settings = array_map(
				function ( $setting ) {
					if ( 'woocommerce_enable_delayed_account_creation' === $setting['id'] ) {
						$setting['desc_tip'] = sprintf(
							/* Translators: %1$s and %2$s are opening and closing <a> tags respectively. */
							__( 'This feature is only available with the Cart & Checkout blocks. %1$sLearn more%2$s.', 'woocommerce' ),
							'<a href="https://woocommerce.com/document/woocommerce-store-editing/customizing-cart-and-checkout">',
							'</a>'
						);
						$setting['disabled']                              = true;
						$setting['value']                                 = 0;
						$setting['custom_attributes']['disabled-tooltip'] = __( 'Your store is using shortcode checkout. Use the Checkout blocks to activate this option.', 'woocommerce' );
					}
					return $setting;
				},
				$account_settings
			);
		}

		/**
		 * Filter account settings.
		 *
		 * @hook woocommerce_account_settings
		 * @since 3.5.0
		 * @param array $account_settings Account settings.
		 */
		return apply_filters( 'woocommerce_' . $this->id . '_settings', $account_settings );
	}

	/**
	 * Output the HTML for the settings.
	 */
	public function output() {
		parent::output();

		// The following code toggles disabled state on the account options based on other values.
		wc_enqueue_js(
			'
			// Move tooltips to label element. This is not possible through the settings field API so this is a workaround
			// until said API is refactored.
			document.querySelectorAll("input[disabled-tooltip]").forEach(function(element) {
				const label = element.closest("label");
				label.setAttribute("disabled-tooltip", element.getAttribute("disabled-tooltip"));
			});

			// This handles settings that are enabled/disabled based on other settings.
			const checkboxes = [
				document.getElementById("woocommerce_enable_signup_and_login_from_checkout"),
				document.getElementById("woocommerce_enable_myaccount_registration"),
				document.getElementById("woocommerce_enable_delayed_account_creation"),
				document.getElementById("woocommerce_enable_signup_from_checkout_for_subscriptions")
			];
			const inputs = [
				document.getElementById("woocommerce_registration_generate_username"),
				document.getElementById("woocommerce_registration_generate_password")
			];
			checkboxes.forEach(cb => cb && cb.addEventListener("change", function() {
				const isChecked = checkboxes.some(cb => cb && cb.checked);
				inputs.forEach(input => {
					if ( ! input ) {
						return;
					}
					input.disabled = !isChecked;
				});
			}));
			checkboxes[0].dispatchEvent(new Event("change")); // Initial state

			// Tracks for customize link.
			if ( typeof window?.wcTracks?.recordEvent === "function" ) {
				document.querySelector("a.delayed-account-creation-customize-link").addEventListener("click", function() {
					window.wcTracks.recordEvent("delayed_account_creation_customize_link_clicked");
				});
			}
		'
		);

		// If the checkout block is not default, delayed account creation is always disabled. Otherwise its based on other settings.
		if ( CartCheckoutUtils::is_checkout_block_default() ) {
			wc_enqueue_js(
				'
				// Guest checkout should toggle off some options.
				const guestCheckout = document.getElementById("woocommerce_enable_guest_checkout");

				if ( guestCheckout ) {
					guestCheckout.addEventListener("change", function() {
						const isChecked = this.checked;
						const input = document.getElementById("woocommerce_enable_delayed_account_creation");
						if ( ! input ) {
							return;
						}
						input.disabled = !isChecked;
					});
					guestCheckout.dispatchEvent(new Event("change")); // Initial state
				}
			'
			);
		}
	}
}

return new WC_Settings_Accounts();
