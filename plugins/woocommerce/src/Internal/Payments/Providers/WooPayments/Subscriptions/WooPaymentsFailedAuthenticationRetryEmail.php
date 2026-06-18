<?php
/**
 * WooPaymentsFailedAuthenticationRetryEmail class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions;

use WC_Email;
use WC_Email_Failed_Order;
use WC_Order;

/**
 * Store-owner email sent when a failed renewal authentication retry is scheduled.
 *
 * @since 11.0.0
 * @internal
 */
class WooPaymentsFailedAuthenticationRetryEmail extends WC_Email_Failed_Order {

	/**
	 * Last retry object for the order.
	 *
	 * @var object|null
	 */
	private ?object $retry = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'failed_authentication_requested';
		$this->title          = __( 'Payment authentication requested email', 'woocommerce' );
		$this->description    = __( 'Payment authentication requested emails are sent to chosen recipient(s) when a subscription renewal payment requires SCA verification and the customer will be asked to authenticate again.', 'woocommerce' );
		$this->heading        = __( 'Automatic renewal payment failed due to authentication required', 'woocommerce' );
		$this->subject        = __( '[{site_title}] Automatic payment failed for {order_number}. Customer asked to authenticate payment and will be notified again {retry_time}', 'woocommerce' );
		$this->email_group    = 'payments';
		$this->template_html  = 'failed-renewal-authentication-requested.php';
		$this->template_plain = 'plain/failed-renewal-authentication-requested.php';
		$this->placeholders   = array(
			'{order_number}' => '',
			'{retry_time}'   => '',
		);

		WC_Email::__construct();

		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Register preview hooks.
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		add_filter( 'woocommerce_email_preview_dummy_order', array( $this, 'get_preview_order' ), 10, 1 );
		add_filter( 'woocommerce_email_preview_dummy_retry', array( $this, 'get_preview_retry' ), 10, 1 );
		add_filter( 'woocommerce_email_preview_placeholders', array( $this, 'get_preview_placeholders' ), 10, 1 );
	}

	/**
	 * Get a preview order.
	 *
	 * @param WC_Order|false $order Preview order.
	 * @return WC_Order
	 */
	public function get_preview_order( $order ): WC_Order {
		if ( $order instanceof WC_Order ) {
			return $order;
		}

		$order = wc_create_order();
		if ( ! $order instanceof WC_Order ) {
			$order = new WC_Order();
		}

		$order->set_status( 'failed' );
		$order->set_billing_first_name( 'John' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_email( 'john.doe@example.com' );
		$order->set_total( '99.99' );
		$order->save();

		return $order;
	}

	/**
	 * Get a preview retry.
	 *
	 * @param object|false $retry Preview retry.
	 * @return object|null
	 */
	public function get_preview_retry( $retry ): ?object {
		if ( is_object( $retry ) ) {
			return $retry;
		}

		if ( ! class_exists( 'WCS_Retry' ) ) {
			return null;
		}

		return new \WCS_Retry(
			array(
				'time'         => time() + DAY_IN_SECONDS,
				'order_id'     => 0,
				'retry_number' => 1,
				'status'       => 'pending',
			)
		);
	}

	/**
	 * Add preview placeholders.
	 *
	 * @param array<string,string> $placeholders Preview placeholders.
	 * @return array<string,string>
	 */
	public function get_preview_placeholders( array $placeholders ): array {
		$retry                        = $this->get_preview_retry( false );
		$placeholders['{retry_time}'] = $this->get_retry_time_label( $retry );

		return $placeholders;
	}

	/**
	 * Get the default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return $this->subject;
	}

	/**
	 * Get the default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return $this->heading;
	}

	/**
	 * Trigger the admin retry email.
	 *
	 * @param int            $order_id Order ID.
	 * @param WC_Order|false $order    Order object.
	 */
	public function trigger( $order_id, $order = false ): void {
		$this->setup_locale();

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( (int) $order_id );
		}

		if ( ! $order instanceof WC_Order || ! $this->is_enabled() || ! $this->get_recipient() ) {
			$this->restore_locale();
			return;
		}

		$this->retry = $this->get_last_retry_for_order( $order );
		if ( null === $this->retry ) {
			wc_get_logger()->info(
				'WCS_Retry_Manager class does not exist. Not able to send admin email about customer notification for authentication required for renewal payment.',
				array( 'source' => 'woopayments-subscriptions' )
			);
			$this->restore_locale();
			return;
		}

		$this->object                         = $order;
		$this->placeholders['{order_number}'] = $order->get_order_number();
		$this->placeholders['{retry_time}']   = $this->get_retry_time_label( $this->retry );

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		$this->restore_locale();
	}

	/**
	 * Get HTML content.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'         => $this->object,
				'retry'         => $this->retry,
				'retry_time'    => $this->get_retry_time_label( $this->retry ),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
			)
		);
	}

	/**
	 * Get plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'retry'         => $this->retry,
				'retry_time'    => $this->get_retry_time_label( $this->retry ),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
			)
		);
	}

	/**
	 * Get the latest retry for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return object|null
	 */
	private function get_last_retry_for_order( WC_Order $order ): ?object {
		$retry_manager_class = 'WCS_Retry_Manager';
		if ( ! class_exists( $retry_manager_class ) || ! is_callable( array( $retry_manager_class, 'store' ) ) ) {
			return null;
		}

		$store = $retry_manager_class::store();
		if ( ! is_object( $store ) || ! method_exists( $store, 'get_last_retry_for_order' ) ) {
			return null;
		}

		$retry = $store->get_last_retry_for_order( $order->get_id() );

		return is_object( $retry ) ? $retry : null;
	}

	/**
	 * Get a human-readable retry time.
	 *
	 * @param object|null $retry Retry object.
	 * @return string
	 */
	private function get_retry_time_label( ?object $retry ): string {
		if ( null === $retry || ! method_exists( $retry, 'get_time' ) || ! function_exists( 'wcs_get_human_time_diff' ) ) {
			return '';
		}

		return (string) wcs_get_human_time_diff( $retry->get_time() );
	}
}
