<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Customers;

/**
 * Tells shoppers their new account was created and how to set its password.
 *
 * Shown on the order received page after an account was created at checkout or with the order confirmation
 * "Create account" block, and on My Account after registering there, so the message reads the same everywhere.
 *
 * @since 11.5.0
 */
class AccountCreatedConfirmation {

	/**
	 * Style handle of the order confirmation "Create account" block, which styles the confirmation box.
	 */
	private const STYLE_HANDLE = 'wc-blocks-style-order-confirmation-create-account';

	/**
	 * Tracks the account created at checkout.
	 *
	 * @var NewCustomerSession
	 */
	private NewCustomerSession $new_customer_session;

	/**
	 * Checks whether the store sends the New account email.
	 *
	 * @var NewAccountEmailWarning
	 */
	private NewAccountEmailWarning $new_account_email;

	/**
	 * Email address of the account registered from My Account in this request.
	 *
	 * @var string
	 */
	private string $registered_email = '';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_before_thankyou', array( $this, 'output_on_order_received' ) );
		add_action( 'woocommerce_before_customer_login_form', array( $this, 'output_after_registration' ) );
	}

	/**
	 * Initialize the class with its dependencies.
	 *
	 * @internal
	 *
	 * @param NewCustomerSession     $new_customer_session Tracks the account created at checkout.
	 * @param NewAccountEmailWarning $new_account_email    Checks whether the store sends the New account email.
	 */
	final public function init( NewCustomerSession $new_customer_session, NewAccountEmailWarning $new_account_email ): void {
		$this->new_customer_session = $new_customer_session;
		$this->new_account_email    = $new_account_email;
	}

	/**
	 * Show the confirmation on the My Account page after the shopper registers there.
	 *
	 * The shopper stays on the same page after registering, so the confirmation is shown in this request.
	 *
	 * @param string $email Email address of the new account.
	 */
	public function show_after_registration( string $email ): void {
		$this->registered_email = $email;
	}

	/**
	 * Output the confirmation on the order received page while the order's new account is unclaimed.
	 *
	 * @internal
	 *
	 * @param mixed $order_id Order ID.
	 */
	public function output_on_order_received( $order_id ): void {
		$order = wc_get_order( absint( $order_id ) );

		if ( $order instanceof \WC_Order && $this->new_customer_session->is_unclaimed_customer_order( $order ) ) {
			$this->output( $order->get_billing_email() );
		}
	}

	/**
	 * Output the confirmation above the My Account login and registration forms.
	 *
	 * @internal
	 */
	public function output_after_registration(): void {
		$email                  = $this->registered_email;
		$this->registered_email = '';

		if ( '' !== $email ) {
			$this->output( $email );
		}
	}

	/**
	 * Get the confirmation markup.
	 *
	 * @param string $email Email address of the new account.
	 * @return string
	 */
	public function render( string $email ): string {
		$lost_password_link = '<a href="' . esc_url( wc_lostpassword_url() ) . '">';

		if ( $this->new_account_email->is_email_enabled() ) {
			$details = array(
				sprintf(
					/* translators: %s: customer email address */
					esc_html__( 'Check %s for a link to set your password.', 'woocommerce' ),
					'<strong>' . esc_html( $email ) . '</strong>'
				),
				sprintf(
					/* translators: %s: link to request a new set-password email */
					esc_html__( 'Didn’t receive it? %s', 'woocommerce' ),
					$lost_password_link . esc_html__( 'Get a new link', 'woocommerce' ) . '</a>'
				),
			);
		} else {
			// The store does not send the New account email, so the shopper has to ask for a link.
			$details = array(
				sprintf(
					/* translators: 1: opening link tag to the lost password page, 2: closing link tag */
					esc_html__( 'To log in, %1$sget a link to set your password%2$s.', 'woocommerce' ),
					$lost_password_link,
					'</a>'
				),
			);
		}

		$html = '<p class="wc-block-order-confirmation-create-account-success__title">' . esc_html__( 'Your account has been created.', 'woocommerce' ) . '</p>';
		foreach ( $details as $detail ) {
			$html .= '<p class="wc-block-order-confirmation-create-account-success__detail">' . $detail . '</p>';
		}

		return '<div class="wc-block-order-confirmation-create-account-success" id="create-account" role="status">' . $html . '</div>';
	}

	/**
	 * Output the confirmation box.
	 *
	 * @param string $email Email address of the new account.
	 */
	private function output( string $email ): void {
		wp_enqueue_style( self::STYLE_HANDLE );

		echo '<div class="wc-block-order-confirmation-create-account wc-account-created-confirmation">' . $this->render( $email ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() escapes its output.
	}
}
