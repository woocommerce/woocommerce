<?php
/**
 * Admin report export failed email (plain text)
 *
 * @package WooCommerce\Admin\Templates\Emails\Plain
 *
 * @var string    $email_heading Email heading.
 * @var string    $report_name   Name of the report that could not be exported.
 * @var string    $date_range    Date range the report covers. Empty for reports without one.
 * @var \WC_Email $email         Email object.
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: report name */
echo esc_html( sprintf( __( 'Your %s Report could not be exported, so there is no file to download.', 'woocommerce' ), $report_name ) );

echo "\n\n";

if ( ! empty( $date_range ) ) {
	/* translators: %s: the date range the report covers, e.g. "June 1, 2025 - June 30, 2025" */
	echo esc_html( sprintf( __( 'Date range: %s', 'woocommerce' ), $date_range ) );

	echo "\n\n";
}

echo esc_html__( 'Please request the report again from Analytics. If this keeps happening, the WooCommerce logs (source: report-csv-exporter) say which part of the export failed.', 'woocommerce' );

echo "\n\n----------------------------------------\n\n";

/**
 * Filter the footer text of the plain text email.
 *
 * @since 11.2.0
 * @param string $footer_text Footer text.
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
