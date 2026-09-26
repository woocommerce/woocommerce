<?php
/**
 * Admin report export download email (plain text)
 *
 * @package WooCommerce\Admin\Templates\Emails\HTML
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %1$s: report name, %2$s: download URL */
echo wp_kses_post( sprintf( __( 'Download your %1$s Report: %2$s', 'woocommerce' ), $report_name, $download_url ) );

echo "\n\n";

/**
 * Date range the report covers, passed in by ReportCSVEmail. Empty for reports without one.
 *
 * @var string $date_range
 */
if ( ! empty( $date_range ) ) {
	/* translators: %s: the date range the report covers, e.g. "June 1, 2025 - June 30, 2025" */
	echo esc_html( sprintf( __( 'Date range: %s', 'woocommerce' ), $date_range ) );

	echo "\n\n";
}

/**
 * Length of time the download link stays valid, passed in by ReportCSVEmail.
 *
 * @var string $retention
 */
/* translators: %s: length of time the download link stays valid, e.g. "1 week" */
echo esc_html( sprintf( __( 'This link is available for %s.', 'woocommerce' ), $retention ) );

echo "\n\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
