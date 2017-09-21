<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wc-metabox closed">
	<h3 class="fixed">
		<button type="button" data-permission_id="<?php echo esc_attr( $download->get_id() ); ?>" rel="<?php echo esc_attr( $download->get_product_id() ) . ',' . esc_attr( $download->get_download_id() ); ?>" class="revoke_access button"><?php _e( 'Revoke access', 'woocommerce' ); ?></button>
		<div class="handlediv" aria-label="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong><?php
			printf(
				'#%s &mdash; %s &mdash; %s: %s &mdash; ',
				esc_html( $product->get_id() ),
				esc_html( apply_filters( 'woocommerce_admin_download_permissions_title', $product->get_name(), $download->get_product_id(), $download->get_order_id(), $download->get_order_key(), $download->get_download_id() ) ),
				esc_html( $file_count ),
				esc_html( wc_get_filename_from_url( $product->get_file_download_path( $download->get_download_id() ) ) )
			);
			printf( _n( 'Downloaded %s time', 'Downloaded %s times', $download->get_download_count(), 'woocommerce' ), esc_html( $download->get_download_count() ) )
		?></strong>
	</h3>
	<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
		<tbody>
			<tr>
				<td>
					<label><?php _e( 'Downloads remaining', 'woocommerce' ); ?></label>
					<input type="hidden" name="permission_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $download->get_id() ); ?>" />
					<input type="number" step="1" min="0" class="short" name="downloads_remaining[<?php echo $loop; ?>]" value="<?php echo esc_attr( $download->get_downloads_remaining() ); ?>" placeholder="<?php esc_attr_e( 'Unlimited', 'woocommerce' ); ?>" />
				</td>
				<td>
					<label><?php _e( 'Access expires', 'woocommerce' ); ?></label>
					<input type="text" class="short date-picker" name="access_expires[<?php echo $loop; ?>]" value="<?php echo ! is_null( $download->get_access_expires() ) ? date_i18n( 'Y-m-d', $download->get_access_expires()->getTimestamp() ) : ''; ?>" maxlength="10" placeholder="<?php esc_attr_e( 'Never', 'woocommerce' ); ?>" pattern="<?php echo esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />
				</td>
				<td>
					<label><?php _e( 'Customer download link', 'woocommerce' ); ?></label>
					<?php
						$download_link = add_query_arg( array(
							'download_file' => $download->get_product_id(),
							'order'         => $download->get_order_key(),
							'email'         => urlencode( $download->get_user_email() ),
							'key'           => $download->get_download_id(),
						), trailingslashit( home_url() ) );

						echo '<a href="' . esc_url( $download_link ) . '">' . esc_html( $file_count ) . '</a>';
					?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
