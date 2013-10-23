<form method="post" action="options.php">
	<?php settings_fields( 'woocommerce_status_settings_fields' ); ?>
	<?php $options = get_option( 'woocommerce_status_options' ); ?>
	<table class="wc_status_table widefat" cellspacing="0">
		<thead class="tools">
			<tr>
				<th colspan="2"><?php _e( 'Tools', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody class="tools">
		<?php foreach( $tools as $action => $tool ) { ?>
			<tr>
				<td><?php echo esc_html( $tool['name'] ); ?></td>
				<td>
					<p>
						<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=wc_status&tab=tools&action=' . $action ), 'debug_action' ); ?>" class="button"><?php echo esc_html( $tool['button'] ); ?></a>
						<span class="description"><?php echo wp_kses_post( $tool['desc'] ); ?></span>
					</p>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td><?php _e( 'Template Debug Mode', 'woocommerce' ); ?></td>
			<td>
				<p>
					<label><input type="checkbox" class="checkbox" name="woocommerce_status_options" value="1" <?php checked('1', $options); ?> /> <?php _e( 'Enabled', 'woocommerce' ); ?></label>
				</p>
				<p>
					<span class="description"><?php _e( 'This tool will disable template overrides for logged-in administrators for debugging purposes.', 'woocommerce' ); ?></span>
				</p>
			</td>
		</tr>
		</tbody>
	</table>
    <p class="submit">
    	<input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>
</form>
