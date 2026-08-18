<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal\Emails;

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Email;
use WC_Order;

/**
 * Merchant order withdrawal request email.
 *
 * @internal Just for internal use.
 */
class OrderWithdrawalRequestedEmail extends WC_Email {

	/**
	 * Form data submitted by the customer.
	 *
	 * @var array<string,string>
	 */
	public array $withdrawal_data = array();

	/**
	 * Matched order, if found.
	 *
	 * @var WC_Order|null
	 */
	public ?WC_Order $matched_order = null;

	/**
	 * Submission timestamp.
	 *
	 * @var int
	 */
	public int $submitted_at = 0;

	/**
	 * Whether the matched order is outside the valid withdrawal window.
	 *
	 * @var bool
	 */
	public bool $outside_withdrawal_window = false;

	/**
	 * Withdrawal window warning message.
	 *
	 * @var string
	 */
	public string $withdrawal_window_warning = '';

	/**
	 * Data formatter.
	 *
	 * @var OrderWithdrawalEmailDataFormatter
	 */
	private OrderWithdrawalEmailDataFormatter $formatter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'order_withdrawal_requested';
		$this->title          = __( 'Order withdrawal request', 'woocommerce' );
		$this->description    = __( 'Sent to chosen recipients when a customer submits an order withdrawal request.', 'woocommerce' );
		$this->email_group    = 'orders';
		$this->template_html  = 'emails/admin-order-withdrawal-requested.php';
		$this->template_plain = 'emails/plain/admin-order-withdrawal-requested.php';
		$this->placeholders   = array(
			'{order_number}'       => '',
			'{order_billing_name}' => '',
		);
		$this->formatter      = new OrderWithdrawalEmailDataFormatter();

		parent::__construct();

		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: Order withdrawal request for order {order_number}', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Order withdrawal request received', 'woocommerce' );
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Review the request details and contact the customer about next steps.', 'woocommerce' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param array<string,string> $data                      Form data.
	 * @param WC_Order|null        $matched_order             Matched order, if found.
	 * @param int                  $submitted_at              Unix timestamp for the submission.
	 * @param bool                 $outside_withdrawal_window Whether the matched order is outside the withdrawal window.
	 * @param string               $withdrawal_window_warning Withdrawal window warning message.
	 * @return bool Whether the email was sent successfully.
	 */
	public function trigger( array $data, ?WC_Order $matched_order, int $submitted_at, bool $outside_withdrawal_window, string $withdrawal_window_warning ): bool {
		$this->setup_locale();

		$this->object                               = $matched_order ? $matched_order : (object) $data;
		$this->withdrawal_data                      = $data;
		$this->matched_order                        = $matched_order;
		$this->submitted_at                         = $submitted_at;
		$this->outside_withdrawal_window            = $outside_withdrawal_window;
		$this->withdrawal_window_warning            = $withdrawal_window_warning;
		$this->placeholders['{order_number}']       = $data[ OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER ];
		$this->placeholders['{order_billing_name}'] = $this->formatter->get_customer_name( $data );

		$sent = $this->send_notification();

		$this->restore_locale();

		return $sent;
	}

	/**
	 * Get email headers.
	 *
	 * @return string
	 */
	public function get_headers() {
		$headers = 'Content-Type: ' . $this->get_content_type() . "\r\n";
		$name    = $this->formatter->get_customer_name( $this->withdrawal_data );
		$email   = $this->withdrawal_data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ] ?? '';

		if ( '' !== $name && is_email( $email ) ) {
			$headers .= 'Reply-to: ' . sanitize_text_field( $name ) . ' <' . sanitize_email( $email ) . ">\r\n";
		}

		if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
			$cc = $this->get_cc_recipient();
			if ( ! empty( $cc ) ) {
				$headers .= 'Cc: ' . sanitize_text_field( $cc ) . "\r\n";
			}

			$bcc = $this->get_bcc_recipient();
			if ( ! empty( $bcc ) ) {
				$headers .= 'Bcc: ' . sanitize_text_field( $bcc ) . "\r\n";
			}
		}

		/**
		 * Filter the email headers.
		 *
		 * @since 2.0.0
		 * @param string   $headers Email headers.
		 * @param string   $email_id Email ID.
		 * @param object|bool $object Email object.
		 * @param WC_Email $email    Email instance.
		 */
		return apply_filters( 'woocommerce_email_headers', $headers, $this->id, $this->object, $this );
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
				'email_heading'             => $this->get_heading(),
				'additional_content'        => $this->get_additional_content(),
				'withdrawal_data'           => $this->withdrawal_data,
				'detail_rows'               => $this->formatter->get_detail_rows( $this->withdrawal_data, $this->submitted_at ),
				'matched_order'             => $this->matched_order,
				'outside_withdrawal_window' => $this->outside_withdrawal_window,
				'withdrawal_window_warning' => $this->withdrawal_window_warning,
				'sent_to_admin'             => true,
				'plain_text'                => false,
				'email'                     => $this,
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
				'email_heading'             => $this->get_heading(),
				'additional_content'        => $this->get_additional_content(),
				'withdrawal_data'           => $this->withdrawal_data,
				'detail_rows'               => $this->formatter->get_detail_rows( $this->withdrawal_data, $this->submitted_at ),
				'matched_order'             => $this->matched_order,
				'outside_withdrawal_window' => $this->outside_withdrawal_window,
				'withdrawal_window_warning' => $this->withdrawal_window_warning,
				'sent_to_admin'             => true,
				'plain_text'                => true,
				'email'                     => $this,
			)
		);
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields(): void {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			),
			'recipient'          => array(
				'title'       => __( 'Recipient(s)', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: WP admin email. */
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
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
