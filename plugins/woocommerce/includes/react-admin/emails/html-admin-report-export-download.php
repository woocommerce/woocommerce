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
<p>
	<?php
		/** @var string $retention Length of time the download link stays valid, passed by ReportCSVEmail. */
		/* translators: %s: length of time the download link stays valid, e.g. "1 week" */
		echo esc_html( sprintf( __( 'This link is available for %s.', 'woocommerce' ), $retention ) );
	?>
</p>
<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
