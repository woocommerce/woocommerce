<?php
/**
 * Class WC_Email_Customer_Review_Request file.
 *
 * @package WooCommerce\Emails
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email_Customer_Review_Request', false ) ) :

	/**
	 * Customer Review Request email.
	 *
	 * A delayed transactional email that invites the customer to review the products
	 * they purchased. The send is scheduled via Action Scheduler (see
	 * `woocommerce_send_review_request`) a configurable number of days after the
	 * order is marked complete. The email links to a per-order Review Order page
	 * protected by the order key.
	 *
	 * @class    WC_Email_Customer_Review_Request
	 * @version  10.8.0
	 * @package  WooCommerce\Classes\Emails
	 */
	class WC_Email_Customer_Review_Request extends WC_Email {

		/**
		 * Minimum allowed delay, in days.
		 */
		private const MIN_DELAY_DAYS = 1;

		/**
		 * Maximum allowed delay, in days.
		 */
		private const MAX_DELAY_DAYS = 60;

		/**
		 * Default delay, in days.
		 */
		private const DEFAULT_DELAY_DAYS = 7;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_review_request';
			$this->customer_email = true;
			$this->title          = __( 'Review request', 'woocommerce' );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/customer-review-request.php';
			$this->template_plain = 'emails/plain/customer-review-request.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Trigger fires via WC_Emails' transactional pipeline after Action Scheduler fires `woocommerce_send_review_request`.
			add_action( 'woocommerce_send_review_request_notification', array( $this, 'trigger' ), 10, 1 );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = __( 'Review request emails are sent to customers a few days after their order is complete, inviting them to leave reviews for the products they purchased.', 'woocommerce' );

			if ( $this->block_email_editor_enabled ) {
				$this->description = __( 'Invites customers to review the products from their completed order.', 'woocommerce' );
			}
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * Wired to `woocommerce_send_review_request`, which Action Scheduler fires
		 * with the order id as its single argument.
		 *
		 * @param int $order_id The order ID.
		 */
		public function trigger( $order_id ): void {
			$this->setup_locale();

			// Reset state from any previous invocation so a call with an invalid
			// order id cannot re-use the previous recipient / placeholders.
			$this->object                         = false;
			$this->recipient                      = '';
			$this->placeholders['{order_date}']   = '';
			$this->placeholders['{order_number}'] = '';

			$order = $order_id ? wc_get_order( $order_id ) : false;

			if ( $order instanceof WC_Order ) {
				$this->object                         = $order;
				$this->recipient                      = $order->get_billing_email();
				$date_created                         = $order->get_date_created();
				$this->placeholders['{order_date}']   = $date_created ? wc_format_datetime( $date_created ) : '';
				$this->placeholders['{order_number}'] = $order->get_order_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() && $this->is_order_eligible_for_send() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Defence-in-depth status check at send time.
		 *
		 * The scheduler unschedules the pending action when the order leaves
		 * `completed`, but a race window or a direct invocation of the action
		 * hook can still reach `trigger()` for an order that is no longer in
		 * an eligible state. Checking the same `woocommerce_review_order_eligible_statuses`
		 * filter the page-load endpoint and submission handler use keeps the
		 * three entry points consistent.
		 *
		 * @since 10.8.0
		 * @return bool
		 */
		protected function is_order_eligible_for_send(): bool {
			if ( ! $this->object instanceof WC_Order ) {
				return false;
			}

			/**
			 * Filter the order statuses that are eligible to receive the review-request email.
			 *
			 * Defaults to `completed` only. Same hook the page-load endpoint and the
			 * submission handler use, so the three entry points stay aligned.
			 *
			 * @since 10.8.0
			 *
			 * @param string[] $eligible_statuses Default: `[ 'completed' ]`.
			 * @param WC_Order $order             Order being inspected.
			 */
			$eligible_statuses = (array) apply_filters(
				'woocommerce_review_order_eligible_statuses',
				array( OrderStatus::COMPLETED ),
				$this->object
			);

			return in_array( $this->object->get_status(), $eligible_statuses, true );
		}

		/**
		 * Get default email subject.
		 *
		 * @since  10.8.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'How was your order from {site_title}?', 'woocommerce' );
		}

		/**
		 * Get default email heading.
		 *
		 * @since  10.8.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Rate your recent purchases', 'woocommerce' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since  10.8.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks again for shopping with us. If you have any questions, reply to this email and we\'ll help out.', 'woocommerce' );
		}

		/**
		 * Get the URL of the per-order Review Order page for this email's order.
		 *
		 * @since  10.8.0
		 * @return string
		 */
		public function get_review_order_url() {
			return $this->object instanceof WC_Order ? wc_get_review_order_url( $this->object ) : '';
		}

		/**
		 * Return the configured send delay in seconds, filterable.
		 *
		 * The stored `delay_days` option is clamped to the supported range before
		 * being converted to seconds. The final value passes through the
		 * `woocommerce_review_request_delay_seconds` filter so integrations can
		 * override it without needing to touch the admin setting.
		 *
		 * @since  10.8.0
		 * @return int Delay in seconds.
		 */
		public function get_delay_seconds() {
			// Use (int) rather than absint() so a negative stored value clamps
			// to MIN_DELAY_DAYS rather than flipping positive.
			$delay_days = (int) $this->get_option( 'delay_days', self::DEFAULT_DELAY_DAYS );
			$delay_days = max( self::MIN_DELAY_DAYS, min( self::MAX_DELAY_DAYS, $delay_days ) );

			/**
			 * Filter the review-request email delay, in seconds.
			 *
			 * @param int $delay_seconds Delay in seconds. Defaults to the admin-configured `delay_days` * DAY_IN_SECONDS.
			 *
			 * @since 10.8.0
			 */
			return (int) apply_filters( 'woocommerce_review_request_delay_seconds', $delay_days * DAY_IN_SECONDS );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'review_order_url'   => $this->get_review_order_url(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
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
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'review_order_url'   => $this->get_review_order_url(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Initialise settings form fields.
		 *
		 * Adds a `delay_days` field on top of the standard WC_Email fields so
		 * merchants can change how long to wait before asking for a review.
		 */
		public function init_form_fields(): void {
			$placeholder_text = sprintf(
				/* translators: %s: list of placeholders */
				__( 'Available placeholders: %s', 'woocommerce' ),
				'<code>' . implode( '</code>, <code>', array_map( 'esc_html', array_keys( $this->placeholders ) ) ) . '</code>'
			);
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'no',
				),
				'delay_days'         => array(
					'title'             => __( 'Delay (days)', 'woocommerce' ),
					'type'              => 'number',
					'description'       => __( 'How many days after the order is marked complete before the review request email is sent.', 'woocommerce' ),
					'default'           => (string) self::DEFAULT_DELAY_DAYS,
					'desc_tip'          => true,
					'custom_attributes' => array(
						'min'  => (string) self::MIN_DELAY_DAYS,
						'max'  => (string) self::MAX_DELAY_DAYS,
						'step' => '1',
					),
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email heading', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'woocommerce' ),
					'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);

			if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
				$this->form_fields['cc']  = $this->get_cc_field();
				$this->form_fields['bcc'] = $this->get_bcc_field();
			}
			if ( $this->block_email_editor_enabled ) {
				$this->form_fields['preheader'] = $this->get_preheader_field();
			}
		}
	}

endif;

return new WC_Email_Customer_Review_Request();
