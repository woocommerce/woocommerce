<?php
/**
 * Admin View: Product import form
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form class="wc-progress-form-content woocommerce-importer" enctype="multipart/form-data" method="post">
	<header>
		<h2><?php esc_html_e( 'Import products from a CSV file', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'This tool allows you to import (or merge) product data to your store from a CSV file.', 'woocommerce' ); ?></p>
	</header>
	<section>
		<table class="form-table woocommerce-importer-options">
			<tbody>
				<tr>
					<th scope="row">
						<label for="upload">
							<?php _e( 'Choose a file from your computer:', 'woocommerce' ); ?>
						</label>
					</th>
					<td>
						<?php
						if ( ! empty( $upload_dir['error'] ) ) {
							?><div class="inline error">
								<p><?php esc_html_e( 'Before you can upload your import file, you will need to fix the following error:', 'woocommerce' ); ?></p>
								<p><strong><?php echo esc_html( $upload_dir['error'] ); ?></strong></p>
							</div><?php
						} else {
							?>
							<input type="file" id="upload" name="import" size="25" />
							<input type="hidden" name="action" value="save" />
							<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $bytes ); ?>" />
							<br><small><?php
								/* translators: %s: maximum upload size */
								printf(
									__( 'Maximum size: %s', 'woocommerce' ),
									$size
								);
							?></small>
							<?php
						}
					?>
					</td>
				</tr>
				<tr>
					<th>
						<label for="file_url"><?php _e( 'OR enter the path to file on your server:', 'woocommerce' ); ?></label>
					</th>
					<td>
						<code><?php echo esc_html( ABSPATH ) . ' '; ?></code><input type="text" id="file_url" name="file_url" size="25" />
					</td>
				</tr>
				<tr>
					<th><label><?php _e( 'CSV Delimiter', 'woocommerce' ); ?></label><br/></th>
					<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
				</tr>
			</tbody>
		</table>
	</section>
	<div class="wc-actions">
		<input type="submit" class="button button-primary button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
		<?php wp_nonce_field( 'woocommerce-csv-importer' ); ?>
	</div>
</form>
