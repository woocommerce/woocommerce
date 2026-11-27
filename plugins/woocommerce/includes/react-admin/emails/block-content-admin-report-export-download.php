<?php
/**
 * Block content for the admin report export download email
 *
 * Used to render information for the email editor WooCommerce content block (BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER).
 *
 * @package WooCommerce\Admin\Templates\Emails\Block
 * @version 11.5.0
 *
 * @var string $report_name  Report name, e.g. "Orders".
 * @var string $date_range   Date range the report covers. Empty for reports without one.
 * @var string $download_url Report download link.
 * @var string $retention    How long the download link stays valid, e.g. "1 week".
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.

wc_get_template(
	'emails/email-button.php',
	array(
		'url'   => $download_url,
		/* translators: %s: report name */
		'label' => sprintf( __( 'Download your %s Report', 'woocommerce' ), $report_name ),
	)
);

if ( ! empty( $date_range ) ) : ?>
<p><?php
	/* translators: %s: the date range the report covers, e.g. "June 1, 2025 - June 30, 2025" */
	echo esc_html( sprintf( __( 'Date range: %s', 'woocommerce' ), $date_range ) );
?></p>
<?php endif; ?>
<p><?php
	/* translators: %s: length of time the download link stays valid, e.g. "1 week" */
	echo esc_html( sprintf( __( 'This link is available for %s.', 'woocommerce' ), $retention ) );
?></p>
