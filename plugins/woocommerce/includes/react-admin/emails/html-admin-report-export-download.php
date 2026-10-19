<?php
/**
 * Admin report export download
 *
 * @package WooCommerce\Admin\Templates\Emails\HTML
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

?>
<a href="<?php echo esc_url( $download_url ); ?>">
	<?php
		/* translators: %s: report name */
		echo esc_html( sprintf( __( 'Download your %s Report', 'woocommerce' ), $report_name ) );
	?>
</a>
<?php
/**
 * Date range the report covers, passed in by ReportCSVEmail. Empty for reports without one.
 *
 * @var string $date_range
 */
if ( ! empty( $date_range ) ) :
	?>
<p>
	<?php
		/* translators: %s: the date range the report covers, e.g. "June 1, 2025 - June 30, 2025" */
		echo esc_html( sprintf( __( 'Date range: %s', 'woocommerce' ), $date_range ) );
	?>
</p>
<?php endif; ?>
<p>
	<?php
		/**
		 * Length of time the download link stays valid, passed in by ReportCSVEmail.
		 *
		 * @var string $retention
		 */
		/* translators: %s: length of time the download link stays valid, e.g. "1 week" */
		echo esc_html( sprintf( __( 'This link is available for %s.', 'woocommerce' ), $retention ) );
	?>
</p>
<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
