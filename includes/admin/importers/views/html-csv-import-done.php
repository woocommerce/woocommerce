<?php
/**
 * Admin View: Importer - Done!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wc-progress-form-content woocommerce-importer">
	<section class="woocommerce-importer-done">
		<?php
			$results = sprintf(
				/* translators: %d: products count */
				_n( 'Imported %s product.', 'Imported %s products.', $imported, 'woocommerce' ),
				'<strong>' . number_format_i18n( $imported ) . '</strong>'
			);

			// @todo create a view to display errors or log with WC_Logger.
			if ( 0 < $failed ) {
				$results .= ' ' . sprintf(
					/* translators: %d: products count */
					_n( 'Failed %s product.', 'Failed %s products.', $failed, 'woocommerce' ),
					'<strong>' . number_format_i18n( $failed ) . '</strong>'
				);
			}

			/* translators: %d: import results */
			echo wp_kses_post( sprintf( __( 'Import complete: %s', 'woocommerce' ), $results ) );
		?>
	</section>
	<div class="wc-actions">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'View products', 'woocommerce' ); ?></a>
	</div>
</div>
