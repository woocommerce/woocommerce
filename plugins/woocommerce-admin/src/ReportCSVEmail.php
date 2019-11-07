<?php
/**
 * Handles emailing users CSV Export download links.
 *
 * @package WooCommerce/Export
 */

namespace Automattic\WooCommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Email', false ) ) {
	include_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

/**
 * ReportCSVEmail Class.
 */
class ReportCSVEmail extends \WC_Email {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'admin_report_export_download';
		$this->template_base  = dirname( __DIR__ ) . '/includes/emails/';
		$this->template_html  = 'html-admin-report-export-download.php';
		$this->template_plain = 'plain-admin-report-export-download.php';
		$this->report_labels  = array(
			'revenue'    => __( 'Revenue', 'woocommerce-admin' ),
			'orders'     => __( 'Orders', 'woocommerce-admin' ),
			'products'   => __( 'Products', 'woocommerce-admin' ),
			'categories' => __( 'Categories', 'woocommerce-admin' ),
			'coupons'    => __( 'Coupons', 'woocommerce-admin' ),
			'taxes'      => __( 'Taxes', 'woocommerce-admin' ),
			'downloads'  => __( 'Downloads', 'woocommerce-admin' ),
			'stock'      => __( 'Stock', 'woocommerce-admin' ),
			'customers'  => __( 'Customers', 'woocommerce-admin' ),
		);

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * This email has no user-facing settings.
	 */
	public function init_form_fields() {}

	/**
	 * This email has no user-facing settings.
	 */
	public function init_settings() {}

	/**
	 * Return email type.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return class_exists( 'DOMDocument' ) ? 'html' : 'plain';
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Your Report Download', 'woocommerce-admin' );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: Your {report_name} Report download is ready', 'woocommerce-admin' );
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
				'report_name'   => $this->report_type,
				'download_url'  => $this->download_url,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			$this->template_base
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
				'report_name'   => $this->report_type,
				'download_url'  => $this->download_url,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int    $user_id User ID to email.
	 * @param string $report_type The type of report export being emailed.
	 * @param string $download_url The URL for downloading the report.
	 */
	public function trigger( $user_id, $report_type, $download_url ) {
		$user               = new \WP_User( $user_id );
		$this->recipient    = $user->user_email;
		$this->download_url = $download_url;

		if ( isset( $this->report_labels[ $report_type ] ) ) {
			$this->report_type                   = $this->report_labels[ $report_type ];
			$this->placeholders['{report_name}'] = $this->report_type;
		}

		$this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}
}
