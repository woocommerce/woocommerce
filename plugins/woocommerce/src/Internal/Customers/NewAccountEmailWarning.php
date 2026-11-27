<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Customers;

/**
 * Tells merchants when the New account email is disabled but customers can still create accounts.
 *
 * New accounts get a generated password, and the New account email carries the link to set it. With the email
 * disabled, customers get no email and have to ask for a link on the lost password page, so the settings screens
 * point this out.
 *
 * @since 11.5.0
 */
class NewAccountEmailWarning {

	/**
	 * Options that let customers create an account.
	 */
	private const ACCOUNT_CREATION_OPTIONS = array(
		'woocommerce_enable_signup_and_login_from_checkout',
		'woocommerce_enable_myaccount_registration',
		'woocommerce_enable_delayed_account_creation',
	);

	/**
	 * Whether the store sends the New account email, which carries the link to set the password.
	 *
	 * @return bool
	 */
	public function is_email_enabled(): bool {
		$email = WC()->mailer()->get_emails()['WC_Email_Customer_New_Account'] ?? null;

		return $email instanceof \WC_Email && $email->is_enabled();
	}

	/**
	 * Whether customers can create an account while the New account email is disabled.
	 *
	 * @return bool
	 */
	public function should_warn(): bool {
		return ! $this->is_email_enabled() && $this->customers_can_create_accounts();
	}

	/**
	 * Whether any setting lets customers create an account.
	 *
	 * @return bool
	 */
	public function customers_can_create_accounts(): bool {
		foreach ( self::ACCOUNT_CREATION_OPTIONS as $option ) {
			if ( 'yes' === get_option( $option, 'no' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Why the email matters, for the Emails list and the email's own settings.
	 *
	 * @return string
	 */
	public function get_status_message(): string {
		return __( 'New customers need this email to set their password and sign in.', 'woocommerce' );
	}

	/**
	 * Help text for the account creation settings, with a link to the email's settings.
	 *
	 * @return string HTML.
	 */
	public function get_account_settings_message(): string {
		return sprintf(
			/* translators: %s: link to the New account email settings */
			esc_html__( 'New customers set their password from the New account email, which is disabled. %s', 'woocommerce' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_email_customer_new_account' ) ) . '">' . esc_html__( 'Enable it', 'woocommerce' ) . '</a>'
		);
	}
}
