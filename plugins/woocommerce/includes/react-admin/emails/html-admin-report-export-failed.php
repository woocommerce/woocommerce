<?php
/**
 * Admin report export failed
 *
 * @package WooCommerce\Admin\Templates\Emails\HTML
 *
 * @var string    $email_heading Email heading.
 * @var string    $report_name   Name of the report that could not be exported.
 * @var string    $date_range    Date range the report covers. Empty for reports without one.
 * @var \WC_Email $email         Email object.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output the email header.
 *
 * @since 11.2.0
 * @hooked WC_Emails::email_header()
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

?>
<p>
	<?php
		/* translators: %s: report name */
		echo esc_html( sprintf( __( 'Your %s Report could not be exported, so there is no file to download.', 'woocommerce' ), $report_name ) );
	?>
</p>
<?php if ( ! empty( $date_range ) ) : ?>
<p>
	<?php
		/* translators: %s: the date range the report covers, e.g. "June 1, 2025 - June 30, 2025" */
		echo esc_html( sprintf( __( 'Date range: %s', 'woocommerce' ), $date_range ) );
	?>
</p>
<?php endif; ?>
<p>
	<?php esc_html_e( 'Please request the report again from Analytics. If this keeps happening, the WooCommerce logs (source: report-csv-exporter) say which part of the export failed.', 'woocommerce' ); ?>
</p>
<?php
/**
 * Output the email footer.
 *
 * @since 11.2.0
 * @hooked WC_Emails::email_footer()
 */
do_action( 'woocommerce_email_footer', $email );
