<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<input type="hidden" name="key_id" value="<?php echo esc_attr( $key_id ); ?>" />

<div id="key-fields" class="settings-panel">
	<h3><?php _e( 'Key Details', 'woocommerce' ); ?></h3>
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="key_description"><?php _e( 'Description', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'Friendly name for identifying this key.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<input name="key_description" id="key_description" type="text" class="input-text regular-input" value="<?php echo esc_attr( $key_data['description'] ); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="key_user"><?php _e( 'User', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php _e( 'Owner of these keys.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<?php
						$curent_user_id = get_current_user_id();
						$user_id        = ! empty( $key_data['user_id'] ) ? absint( $key_data['user_id'] ) : $curent_user_id;
						$user           = get_user_by( 'id', $user_id );
						$user_string    = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email );
					?>
					<input type="hidden" class="wc-customer-search" name="key_user" data-placeholder="<?php esc_html_e( 'Search for a customer&hellip;', 'woocommerce' ); ?>" data-selected="<?php echo esc_attr( $user_string ); ?>" value="<?php echo esc_attr( $user_id ); ?>" data-allow_clear="true" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="key_permissions"><?php _e( 'Permissons', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php _e( 'Select the access type of these keys.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<select name="key_permissions" id="key_permissions" class="wc-enhanced-select">
						<?php
							$permissions = array(
								'read'       => __( 'Read', 'woocommerce' ),
								'write'      => __( 'Write', 'woocommerce' ),
								'read_write' => __( 'Read/Write', 'woocommerce' ),
							);

							foreach ( $permissions as $permission_id => $permission_name ) : ?>
							<option value="<?php echo esc_attr( $permission_id ); ?>" <?php selected( $key_data['permissions'], $permission_id, true ); ?>><?php echo esc_html( $permission_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php if ( ! empty( $key_data['consumer_key'] ) && ! empty( $key_data['consumer_secret'] ) ) : ?>
				<tr valign="top" id="webhook-action-event-wrap">
					<th scope="row" class="titledesc">
						<?php _e( 'Consumer Key', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<code id="key_consumer_key"><?php echo esc_html( $key_data['consumer_key'] ); ?></code> <button type="button" class="button-secondary copy-key" data-tip="<?php _e( 'Copied!', 'woocommerce' ); ?>"><?php _e( 'Copy', 'woocommerce' ); ?></button>
					</td>
				</tr>
				<tr valign="top" id="webhook-action-event-wrap">
					<th scope="row" class="titledesc">
						<label for="key_consumer_secret"><?php _e( 'Consumer Secret', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<code id="key_consumer_secret"><?php echo esc_html( $key_data['consumer_secret'] ); ?></code> <button type="button" class="button-secondary copy-key" data-tip="<?php _e( 'Copied!', 'woocommerce' ); ?>"><?php _e( 'Copy', 'woocommerce' ); ?></button>
					</td>
				</tr>
				<tr valign="top" id="webhook-action-event-wrap">
					<th scope="row" class="titledesc">
						<?php _e( 'QRCode', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<div id="qrcode_wrap" data-consumer_key="<?php echo esc_attr( $key_data['consumer_key'] ); ?>" data-consumer_secret="<?php echo esc_attr( $key_data['consumer_secret'] ); ?>"></div>

						<script>
							jQuery( function( $ ) {
								// Copy to clipboard
								$( '.copy-key' ).tipTip({
									'attribute':  'data-tip',
									'activation': 'click',
									'fadeIn':     50,
									'fadeOut':    50,
									'delay':      0
								});

								$( document.body ).on( 'copy', '.copy-key', function( e ) {
									e.clipboardData.clearData();
									e.clipboardData.setData( 'text/plain', $.trim( $( this ).prev( 'code' ).html() ) );
									e.preventDefault();
								});

								// Generate QR Code
								var qrcodeWrap = $( '#qrcode_wrap' );
								qrcodeWrap.qrcode({
									text: qrcodeWrap.data( 'consumer_key' ) + '|' + qrcodeWrap.data( 'consumer_secret' ),
									width: 120,
									height: 120
								});
							});
						</script>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php do_action( 'woocommerce_admin_key_fields', $key_data ); ?>

	<?php
		if ( 0 == $key_id ) {
			submit_button( __( 'Generate API Key', 'woocommerce' ), 'primary', 'update_api_key' );
		} else {
			?>
			<p class="submit">
				<?php submit_button( __( 'Save Changes', 'woocommerce' ), 'primary', 'update_api_key', false ); ?>
				<a style="color: #a00; text-decoration: none; margin-left: 10px;" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'revoke-key' => $key_id ), admin_url( 'admin.php?page=wc-settings&tab=api&section=keys' ) ), 'revoke' ) ); ?>"><?php _e( 'Revoke Key', 'woocommerce' ); ?></a>
			</p>
			<?php
		}
	?>
</div>
