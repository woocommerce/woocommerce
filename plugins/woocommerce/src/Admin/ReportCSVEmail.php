<?php
/**
 * Handles emailing users CSV Export download links.
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
	 * Report labels.
	 *
	 * @var array
	 */
	protected $report_labels;

	/**
	 * Report type (e.g. 'customers').
	 *
	 * @var string
	 */
	protected $report_type;

	/**
	 * Download URL.
	 *
	 * @var string
	 */
	protected $download_url;

	/**
	 * Date range the report covers, formatted for display. Empty when the report has no range.
	 *
	 * @var string
	 */
	protected $report_date_range = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->placeholders['{report_date_range}'] = '';

		$this->id             = 'admin_report_export_download';
		$this->template_base  = WC()->plugin_path() . '/includes/react-admin/emails/';
		$this->template_html  = 'html-admin-report-export-download.php';
		$this->template_plain = 'plain-admin-report-export-download.php';

		/**
		 * Used to customise report email labels.
		 *
		 * @since 9.9.0
		 *
		 * @param string[] $labels An array of labels.
		 *
		 * @return string[] An Array of labels.
		 */
		$this->report_labels = apply_filters(
			'woocommerce_report_export_email_labels',
			array(
				'categories' => __( 'Categories', 'woocommerce' ),
				'coupons'    => __( 'Coupons', 'woocommerce' ),
				'customers'  => __( 'Customers', 'woocommerce' ),
				'downloads'  => __( 'Downloads', 'woocommerce' ),
				'orders'     => __( 'Orders', 'woocommerce' ),
				'products'   => __( 'Products', 'woocommerce' ),
				'revenue'    => __( 'Revenue', 'woocommerce' ),
				'stock'      => __( 'Stock', 'woocommerce' ),
				'taxes'      => __( 'Taxes', 'woocommerce' ),
				'variations' => __( 'Variations', 'woocommerce' ),
			)
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
		return __( 'Your Report Download', 'woocommerce' );
	}

	/**
	 * Get email subject.
	 *
	 * Says which period the report covers when it has one, so a merchant running the same report
	 * over several date ranges can tell the emails apart without opening them.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		if ( '' !== $this->report_date_range ) {
			return __( '[{site_title}]: Your {report_name} Report for {report_date_range} is ready', 'woocommerce' );
		}

		return __( '[{site_title}]: Your {report_name} Report download is ready', 'woocommerce' );
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
				'date_range'    => $this->report_date_range,
				'download_url'  => $this->download_url,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
				'retention'     => $this->get_retention_period(),
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
				'date_range'    => $this->report_date_range,
				'download_url'  => $this->download_url,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
				'retention'     => $this->get_retention_period(),
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get how long the emailed download link stays valid, for display.
	 *
	 * @since 11.2.0
	 * @return string Human readable length of time, e.g. "1 week".
	 */
	protected function get_retention_period() {
		return human_time_diff( 0, ReportExporter::EXPORT_RETENTION_PERIOD );
	}

	/**
	 * Set the date range the report covers, so the email can say which period it is for.
	 *
	 * Call before trigger(). Reports that are not limited to a period, such as Stock, leave it unset.
	 *
	 * @since 11.2.0
	 * @param string $date_range The date range the report covers, formatted for display.
	 * @return void
	 */
	public function set_report_date_range( $date_range ) {
		$this->report_date_range = is_string( $date_range ) ? $date_range : '';

		$this->placeholders['{report_date_range}'] = $this->report_date_range;
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
