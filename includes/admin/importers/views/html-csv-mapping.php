<?php
/**
 * Admin View: Importer - CSV mapping
 *
 * @var array $headers CSV headers.
 * @var array $sample  CSV sample.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form action="<?php echo esc_url( admin_url( 'admin.php?import=' . $this->import_page . '&step=2' ) ); ?>" method="post">
	<h2 class="title"><?php esc_html_e( 'Fields to map', 'woocommerce' ); ?></h2>
	<p><?php esc_html_e( 'Select fields from your CSV file to map against products fields.', 'woocommerce' ); ?></p>

	<?php wp_nonce_field( 'woocommerce-csv-importer' ); ?>
	<input type="hidden" name="file_id" value="<?php echo esc_attr( $this->id ); ?>" />
	<input type="hidden" name="file_url" value="<?php echo esc_attr( $this->file_url ); ?>" />
	<input type="hidden" name="delimiter" value="<?php echo esc_attr( $this->delimiter ); ?>" />

	<table class="widefat wc-importer__mapping--table">
		<thead>
			<tr>
				<th><?php _e( 'Column name', 'woocommerce' ); ?></th>
				<th><?php _e( 'Sample value', 'woocommerce' ); ?></th>
				<th><?php _e( 'Map to field', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $headers as $index => $name ) : ?>
				<tr>
					<td width="20%"><?php echo esc_html( $name ); ?></td>
					<td width="30%"><code><?php echo ! empty( $sample[ $index ] ) ? esc_html( $sample[ $index ] ) : '-'; ?></code></td>
					<td>
						<select name="map_to[<?php echo esc_attr( $name ); ?>]">
							<option value=""><?php esc_html_e( 'Do not import', 'woocommerce' ); ?></option>

							<?php foreach ( $this->get_mapping_options( $name ) as $key => $value ) : ?>
								<?php if ( is_array( $value ) ) : ?>
									<optgroup label="<?php echo esc_attr( $value['name'] ); ?>">
										<?php foreach ( $value['options'] as $sub_key => $sub_value ) : ?>
											<option value="<?php echo esc_attr( $sub_key ); ?>" <?php selected( $name, $sub_key ); ?>><?php echo esc_html( $sub_value ); ?></option>
										<?php endforeach ?>
									</optgroup>
								<?php else : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $name, $key ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endif; ?>
							<?php endforeach ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php submit_button( __( 'Submit', 'woocommerce' ), 'secondary' ); ?>
</form>
