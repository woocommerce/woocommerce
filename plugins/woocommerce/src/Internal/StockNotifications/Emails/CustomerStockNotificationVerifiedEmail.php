<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use WC_Email;

/**
 * Back in stock notification email class.
 */
class CustomerStockNotificationVerifiedEmail extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_stock_notification_verified';
		$this->customer_email = true;

		$this->title       = __( 'Back in stock sign-up confirmation', 'woocommerce' );
		$this->description = __( 'Email sent to customers after completing the sign-up process successfully.', 'woocommerce' );

		$this->template_html  = 'emails/customer-stock-notification-verified.php';
		$this->template_plain = 'emails/plain/customer-stock-notification-verified.php';
		$this->placeholders   = array(
			'{product_name}' => '',
			'{site_title}'   => '',
		);

		add_action( 'woocommerce_email_stock_notification_verified_notification', array( $this, 'trigger' ), 10, 1 );

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'You have joined the "{product_name}" waitlist.', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Sign-up successful', 'woocommerce' );
	}

	/**
	 * Get default email content.
	 *
	 * @return string
	 */
	public function get_default_intro_content() {
		return __( 'Thanks for joining the waitlist! You will hear from us again when "{product_name}" is back in stock.', 'woocommerce' );
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Thanks for shopping with us.', 'woocommerce' );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_intro_content() {
		/**
		 * Allows modifying the email introduction content.
		 *
		 * @since  10.2.0
		 *
		 * @return string
		 */
		return apply_filters( 'woocommerce_email_stock_notification_intro_content', $this->format_string( $this->get_option_or_transient( 'intro_content', $this->get_default_intro_content() ) ), $this->object, $this );
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array_merge(
				$this->get_additional_template_args(),
				array(
					'notification'       => $this->object,
					'product'            => $this->object->get_product(),
					'email_heading'      => $this->get_heading(),
					'intro_content'      => $this->get_intro_content(),
					'additional_content' => $this->get_additional_content(),
					'plain_text'         => false,
					'email'              => $this,
				),
			),
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array_merge(
				$this->get_additional_template_args(),
				array(
					'notification'       => $this->object,
					'product'            => $this->object->get_product(),
					'email_heading'      => $this->get_heading(),
					'intro_content'      => $this->get_intro_content(),
					'additional_content' => $this->get_additional_content(),
					'plain_text'         => true,
					'email'              => $this,
				),
			),
		);
	}

	/**
	 * Get template args.
	 *
	 * @return array
	 */
	private function get_additional_template_args(): array {
		$notification    = $this->object;
		$unsubscribe_key = $notification->get_unsubscribe_key( true );
		$user            = get_user_by( 'email', $notification->get_user_email() );
		$is_guest        = ! is_a( $user, 'WP_User' );

		return array(
			'is_guest'         => $is_guest,
			'unsubscribe_link' => add_query_arg(
				array(
					'email_link_action_key' => $unsubscribe_key,
					'notification_id'       => $notification->get_id(),
				),
				get_option( 'siteurl' )
			),
		);
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param Notification|int $notification The notification object or ID.
	 */
	public function trigger( $notification ) {
		$this->setup_locale();

		if ( is_numeric( $notification ) ) {
			$notification = Factory::get_notification( $notification );
		}

		if ( ! $notification instanceof Notification ) {
			return;
		}

		$product = $notification->get_product();
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->maybe_setup_notification_locale( $notification );
		$this->prepare_email( $notification );

		if ( $this->is_enabled() && $this->get_recipient() ) {

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		}

		$this->maybe_restore_notification_locale( $notification );
		$this->restore_locale();
	}

	/**
	 * Prepares the email based on the notification data.
	 *
	 * @param Notification $notification Notification.
	 * @return void
	 */
	public function prepare_email( Notification $notification ): void {
		$this->object                         = $notification;
		$this->recipient                      = $notification->get_user_email();
		$product                              = $notification->get_product();
		$this->placeholders['{product_name}'] = preg_replace( $this->plain_search, $this->plain_replace, $product->get_name() );
		$this->placeholders['{site_title}']   = preg_replace( $this->plain_search, $this->plain_replace, $this->get_blogname() );
	}


	/**
	 * Setup notification locale if necessary based on notification meta.
	 *
	 * @param Notification $notification Notification object.
	 */
	private function maybe_setup_notification_locale( $notification ) {
		$customer_locale = $notification->get_meta( '_customer_locale' );
		if ( ! empty( $customer_locale ) ) {
			switch_to_locale( $customer_locale );
		}
	}

	/**
	 * Restore locale if previously switched.
	 *
	 * @param Notification $notification Notification object.
	 */
	private function maybe_restore_notification_locale( $notification ) {
		$customer_locale = $notification->get_meta( '_customer_locale' );
		if ( ! empty( $customer_locale ) ) {
			restore_previous_locale();
		}
	}

	/**
	 * Initialize Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		parent::init_form_fields();

		if ( ! is_array( $this->form_fields ) ) {
			return;
		}

		/* translators: %s: list of placeholders */
		$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

		$intro_content_field = array(
			'title'       => __( 'Email content', 'woocommerce' ),
			'description' => __( 'Text to appear below the main e-mail header.', 'woocommerce' ) . ' ' . $placeholder_text,
			'css'         => 'width: 400px; height: 75px;',
			'placeholder' => $this->get_default_intro_content(),
			'type'        => 'textarea',
			'desc_tip'    => true,
		);

		// Find `heading` key.
		$inject_index = array_search( 'heading', array_keys( $this->form_fields ), true );
		if ( $inject_index ) {
			++$inject_index;
		} else {
			$inject_index = 0;
		}

		// Inject.
		$this->form_fields = array_slice( $this->form_fields, 0, $inject_index, true ) + array( 'intro_content' => $intro_content_field ) + array_slice( $this->form_fields, $inject_index, count( $this->form_fields ) - $inject_index, true );
	}
}
