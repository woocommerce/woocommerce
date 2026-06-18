<?php
/**
 * WooPaymentsPostKycActivationEmail class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WC_Email;

/**
 * Store-owner email sent after KYC completion when the store has not made its first live WooPayments sale.
 *
 * @since 11.0.0
 * @internal
 */
class WooPaymentsPostKycActivationEmail extends WC_Email {

	/**
	 * Nudge sequence stage in days after KYC completion.
	 *
	 * @var int
	 */
	public int $stage = 7;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'wcpay_post_kyc_activation';
		$this->customer_email = false;
		$this->title          = __( 'First sale reminder', 'woocommerce' );
		$this->description    = __( "We'll send a couple of reminders during your first month of accepting payments, to help you bring in your first sale. Stops automatically once you've taken one.", 'woocommerce' );
		$this->email_group    = 'payments';
		$this->template_html  = 'emails/post-kyc-activation.php';
		$this->template_plain = 'emails/plain/post-kyc-activation.php';
		$this->plugin_id      = 'woocommerce_woocommerce_payments_';
		$this->placeholders   = array(
			'{stage}'      => '',
			'{site_title}' => $this->get_blogname(),
		);

		parent::__construct();

		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Ready for your first sale on {site_title}?', 'woocommerce' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( "Your store is ready - let's make your first sale", 'woocommerce' );
	}

	/**
	 * Initialize settings fields.
	 *
	 * The templates render stage-specific headings, so a merchant-level heading override would flatten all stages.
	 */
	public function init_form_fields(): void {
		parent::init_form_fields();
		unset( $this->form_fields['heading'] );
	}

	/**
	 * Trigger sending the staged email.
	 *
	 * @param int $stage Stage day.
	 * @return bool True when the mailer reports a successful send.
	 */
	public function trigger( int $stage ): bool {
		if ( ! in_array( $stage, array( 7, 14, 30 ), true ) ) {
			return false;
		}

		$this->stage                   = $stage;
		$this->placeholders['{stage}'] = (string) $stage;

		$this->setup_locale();

		$sent = false;
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();

		if ( class_exists( '\WC_Tracks' ) ) {
			if ( $sent ) {
				\WC_Tracks::record_event( 'wcpay_post_kyc_activation_email_sent', array( 'stage' => $stage ) );
			} elseif ( $this->is_enabled() && $this->get_recipient() ) {
				\WC_Tracks::record_event( 'wcpay_post_kyc_activation_email_send_failed', array( 'stage' => $stage ) );
			}
		}

		return $sent;
	}

	/**
	 * Get the email CTA URL.
	 *
	 * @return string
	 */
	public function get_cta_url(): string {
		return add_query_arg(
			array(
				'page'                 => 'wc-admin',
				'path'                 => '/marketing',
				'wcpay_referrer'       => 'post_kyc_email',
				'wcpay_referrer_stage' => $this->stage,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the email CTA label.
	 *
	 * @return string
	 */
	public function get_cta_label(): string {
		return __( 'Promote my store', 'woocommerce' );
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
				'stage'              => $this->stage,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'cta_url'            => $this->get_cta_url(),
				'cta_label'          => $this->get_cta_label(),
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $this,
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
				'stage'              => $this->stage,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'cta_url'            => $this->get_cta_url(),
				'cta_label'          => $this->get_cta_label(),
				'sent_to_admin'      => true,
				'plain_text'         => true,
				'email'              => $this,
			)
		);
	}

	/**
	 * Get default additional content.
	 *
	 * @return string
	 */
	public function get_default_additional_content(): string {
		return __( 'Thanks for choosing WooPayments.', 'woocommerce' );
	}
}
