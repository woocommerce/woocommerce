<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal\Emails;

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;
use WC_Email;

/**
 * Customer order withdrawal request email.
 *
 * @internal Just for internal use.
 */
class CustomerOrderWithdrawalRequestedEmail extends WC_Email {

	/**
	 * Form data submitted by the customer.
	 *
	 * @var array<string,string>
	 */
	public array $withdrawal_data = array();

	/**
	 * Submission timestamp.
	 *
	 * @var int
	 */
	public int $submitted_at = 0;

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
		$this->id             = 'customer_order_withdrawal_requested';
		$this->customer_email = true;
		$this->title          = __( 'Order withdrawal request received', 'woocommerce' );
		$this->description    = __( 'Sent to customers when their order withdrawal request is received.', 'woocommerce' );
		$this->email_group    = 'order-changes';
		$this->template_html  = 'emails/customer-order-withdrawal-requested.php';
		$this->template_plain = 'emails/plain/customer-order-withdrawal-requested.php';
		$this->placeholders   = array(
			'{order_number}'       => '',
			'{order_billing_name}' => '',
		);
		$this->formatter      = new OrderWithdrawalEmailDataFormatter();

		parent::__construct();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'We received your withdrawal request', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'We received your withdrawal request', 'woocommerce' );
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'We will review your request and contact you about next steps, including any refund due.', 'woocommerce' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param array<string,string> $data         Form data.
	 * @param int                  $submitted_at Unix timestamp for the submission.
	 * @return bool Whether the email was sent successfully.
	 */
	public function trigger( array $data, int $submitted_at ): bool {
		$this->setup_locale();

		$this->object                               = (object) $data;
		$this->withdrawal_data                      = $data;
		$this->submitted_at                         = $submitted_at;
		$this->recipient                            = $data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ];
		$this->placeholders['{order_number}']       = $data[ OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER ];
		$this->placeholders['{order_billing_name}'] = $this->formatter->get_customer_name( $data );

		$sent = $this->send_notification();

		$this->restore_locale();

		return $sent;
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
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'withdrawal_data'    => $this->withdrawal_data,
				'detail_rows'        => $this->formatter->get_detail_rows( $this->withdrawal_data, $this->submitted_at ),
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
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'withdrawal_data'    => $this->withdrawal_data,
				'detail_rows'        => $this->formatter->get_detail_rows( $this->withdrawal_data, $this->submitted_at ),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			)
		);
	}
}
