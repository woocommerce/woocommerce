<?php
/**
 * WooPaymentsFailedRenewalAuthenticationEmail class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions;

use WC_Email;
use WC_Order;

/**
 * Customer email sent when a subscription renewal needs payment authentication.
 *
 * @since 11.0.0
 * @internal
 */
class WooPaymentsFailedRenewalAuthenticationEmail extends WC_Email {

	/**
	 * The original WooCommerce Subscriptions renewal invoice email.
	 *
	 * @var WC_Email|null
	 */
	public ?WC_Email $original_email = null;

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $email_classes Existing WooCommerce email classes.
	 */
	public function __construct( array $email_classes = array() ) {
		$this->id             = 'failed_renewal_authentication';
		$this->title          = __( 'Failed subscription renewal SCA authentication', 'woocommerce' );
		$this->description    = __( 'Sent to a customer when a renewal fails because the transaction requires SCA verification. The email contains renewal order information and payment links.', 'woocommerce' );
		$this->customer_email = true;
		$this->email_group    = 'payments';
		$this->template_html  = 'failed-renewal-authentication.php';
		$this->template_plain = 'plain/failed-renewal-authentication.php';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		if ( isset( $email_classes['WCS_Email_Customer_Renewal_Invoice'] ) && $email_classes['WCS_Email_Customer_Renewal_Invoice'] instanceof WC_Email ) {
			$this->original_email = $email_classes['WCS_Email_Customer_Renewal_Invoice'];
		}

		parent::__construct();
	}

	/**
	 * Register email hooks.
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		add_action( 'woocommerce_woocommerce_payments_payment_requires_action', array( $this, 'trigger' ) );
	}

	/**
	 * Get HTML content.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		if ( ! $this->object instanceof WC_Order ) {
			return '';
		}

		return wc_get_template_html(
			$this->template_html,
			array(
				'order'             => $this->object,
				'email_heading'     => $this->get_heading(),
				'sent_to_admin'     => false,
				'plain_text'        => false,
				'authorization_url' => $this->get_authorization_url( $this->object ),
				'email'             => $this,
			)
		);
	}

	/**
	 * Get plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		if ( ! $this->object instanceof WC_Order ) {
			return '';
		}

		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'             => $this->object,
				'email_heading'     => $this->get_heading(),
				'sent_to_admin'     => false,
				'plain_text'        => true,
				'authorization_url' => $this->get_authorization_url( $this->object ),
				'email'             => $this,
			)
		);
	}

	/**
	 * Get the URL customers use to authorize the renewal payment.
	 *
	 * @param WC_Order $order Renewal order.
	 * @return string
	 */
	public function get_authorization_url( WC_Order $order ): string {
		return $order->get_checkout_payment_url( false );
	}

	/**
	 * Initialize settings fields.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		parent::init_form_fields();
		$base_fields = $this->form_fields;

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => _x( 'Enable/disable', 'an email notification', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			),
			'subject'    => $base_fields['subject'],
			'heading'    => $base_fields['heading'],
			'email_type' => $base_fields['email_type'],
		);
	}

	/**
	 * Get the default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Payment authorization needed for renewal of {site_title} order {order_number}', 'woocommerce' );
	}

	/**
	 * Get the default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Payment authorization needed for renewal of order {order_number}', 'woocommerce' );
	}

	/**
	 * Trigger the customer authentication email.
	 *
	 * @param WC_Order $order Renewal order.
	 * @return void
	 */
	public function trigger( WC_Order $order ): void {
		$this->setup_locale();

		if ( ! $this->is_subscription_related_order( $order ) || ! $this->is_enabled() ) {
			$this->restore_locale();
			return;
		}

		$date_created                         = $order->get_date_created();
		$this->object                         = $order;
		$this->recipient                      = $order->get_billing_email();
		$this->placeholders['{order_date}']   = null === $date_created ? '' : wc_format_datetime( $date_created );
		$this->placeholders['{order_number}'] = $order->get_order_number();

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		if ( $this->original_email instanceof WC_Email ) {
			remove_action( 'woocommerce_generated_manual_renewal_order_renewal_notification', array( $this->original_email, 'trigger' ) );
			remove_action( 'woocommerce_order_status_failed_renewal_notification', array( $this->original_email, 'trigger' ) );
		}

		add_filter( 'wcs_get_retry_rule_raw', array( $this, 'prevent_retry_notification_email' ), 100, 3 );
		add_filter( 'wcs_get_retry_rule_raw', array( $this, 'set_store_owner_custom_email' ), 100, 3 );

		$this->restore_locale();
	}

	/**
	 * Prevent customer retry notifications after the authentication email is sent.
	 *
	 * @param array<string,mixed> $rule_array   Retry rule.
	 * @param int                 $retry_number Retry number.
	 * @param int                 $order_id     Order ID.
	 * @return array<string,mixed>
	 */
	public function prevent_retry_notification_email( array $rule_array, int $retry_number, int $order_id ): array {
		unset( $retry_number );

		if ( $this->is_current_order_id( $order_id ) ) {
			$rule_array['email_template_customer'] = '';
		}

		return $rule_array;
	}

	/**
	 * Send the WooPayments authentication retry email to the store owner.
	 *
	 * @param array<string,mixed> $rule_array   Retry rule.
	 * @param int                 $retry_number Retry number.
	 * @param int                 $order_id     Order ID.
	 * @return array<string,mixed>
	 */
	public function set_store_owner_custom_email( array $rule_array, int $retry_number, int $order_id ): array {
		unset( $retry_number );

		if ( $this->is_current_order_id( $order_id ) && '' !== (string) ( $rule_array['email_template_admin'] ?? '' ) ) {
			$rule_array['email_template_admin'] = 'WC_Payments_Email_Failed_Authentication_Retry';
		}

		return $rule_array;
	}

	/**
	 * Tell whether the order is related to a subscription.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function is_subscription_related_order( WC_Order $order ): bool {
		$order_id = $order->get_id();

		return ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order_id ) )
			|| ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order_id ) )
			|| ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Tell whether an ID matches the currently bound order.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	private function is_current_order_id( int $order_id ): bool {
		return $this->object instanceof WC_Order && $this->object->get_id() === $order_id;
	}
}
