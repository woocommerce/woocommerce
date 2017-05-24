<?php
/**
 * Admin View: Importer - CSV mapping
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form class="wc-progress-form-content woocommerce-importer" method="post" action="<?php echo esc_url( $this->get_next_step_link() ) ?>">
	<header>
		<h2><?php esc_html_e( 'Map CSV fields to products', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Select fields from your CSV file to map against products fields, or to ignore during import.', 'woocommerce' ); ?></p>
	</header>

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
				<?php $mapped_value = $mapped_items[ $index ]; ?>
				<tr>
					<td width="20%"><?php echo esc_html( $name ); ?></td>
					<td width="30%"><code><?php echo '' !== ( $sample[ $index ] ) ? esc_html( $sample[ $index ] ) : '-'; ?></code></td>
					<td>
						<select name="map_to[<?php echo esc_attr( $name ); ?>]">
							<option value=""><?php esc_html_e( 'Do not import', 'woocommerce' ); ?></option>
							<?php foreach ( $this->get_mapping_options( $mapped_value ) as $key => $value ) : ?>
								<?php if ( is_array( $value ) ) : ?>
									<optgroup label="<?php echo esc_attr( $value['name'] ); ?>">
										<?php foreach ( $value['options'] as $sub_key => $sub_value ) : ?>
											<option value="<?php echo esc_attr( $sub_key ); ?>" <?php selected( $mapped_value, $sub_key ); ?>><?php echo esc_html( $sub_value ); ?></option>
										<?php endforeach ?>
									</optgroup>
								<?php else : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $mapped_value, $key ); ?>><?php echo esc_html( $value ); ?></option>
								<?php endif; ?>
							<?php endforeach ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="wc-actions">
		<input type="submit" class="button button-primary button-next" value="<?php esc_attr_e( 'Run the importer', 'woocommerce' ); ?>" name="save_step" />
		<input type="hidden" name="file" value="<?php echo esc_attr( $this->file ); ?>" />
		<input type="hidden" name="delimiter" value="<?php echo esc_attr( $this->delimiter ); ?>" />
		<?php wp_nonce_field( 'woocommerce-csv-importer' ); ?>
	</div>
</form>
