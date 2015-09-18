<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<input type="hidden" name="webhook_id" value="<?php echo esc_attr( $webhook->id ); ?>" />

<div id="webhook-options" class="settings-panel">
	<h3><?php _e( 'Webhook Data', 'woocommerce' ); ?></h3>
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="webhook_name"><?php _e( 'Name', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php echo sprintf( __( 'Friendly name for identifying this webhook, defaults to Webhook created on %s.', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<input name="webhook_name" id="webhook_name" type="text" class="input-text regular-input" value="<?php echo esc_attr( $webhook->get_name() ); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="webhook_status"><?php _e( 'Status', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'The options are &quot;Active&quot; (delivers payload), &quot;Paused&quot; (does not deliver), or &quot;Disabled&quot; (does not deliver due delivery failures).', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<select name="webhook_status" id="webhook_status" class="wc-enhanced-select">
						<?php
							$statuses       = wc_get_webhook_statuses();
							$current_status = $webhook->get_status();

							foreach ( $statuses as $status_slug => $status_name ) : ?>
							<option value="<?php echo esc_attr( $status_slug ); ?>" <?php selected( $current_status, $status_slug, true ); ?>><?php echo esc_html( $status_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="webhook_topic"><?php _e( 'Topic', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'Select when the webhook will fire.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<select name="webhook_topic" id="webhook_topic" class="wc-enhanced-select">
						<?php
							$topic_data = WC_Admin_Webhooks::get_topic_data( $webhook );

							$topics = apply_filters( 'woocommerce_webhook_topics', array(
								''                 => __( 'Select an option&hellip;', 'woocommerce' ),
								'coupon.created'   => __( 'Coupon Created', 'woocommerce' ),
								'coupon.updated'   => __( 'Coupon Updated', 'woocommerce' ),
								'coupon.deleted'   => __( 'Coupon Deleted', 'woocommerce' ),
								'customer.created' => __( 'Customer Created', 'woocommerce' ),
								'customer.updated' => __( 'Customer Updated', 'woocommerce' ),
								'customer.deleted' => __( 'Customer Deleted', 'woocommerce' ),
								'order.created'    => __( 'Order Created', 'woocommerce' ),
								'order.updated'    => __( 'Order Updated', 'woocommerce' ),
								'order.deleted'    => __( 'Order Deleted', 'woocommerce' ),
								'product.created'  => __( 'Product Created', 'woocommerce' ),
								'product.updated'  => __( 'Product Updated', 'woocommerce' ),
								'product.deleted'  => __( 'Product Deleted', 'woocommerce' ),
								'action'           => __( 'Action', 'woocommerce' ),
								'custom'           => __( 'Custom', 'woocommerce' )
							) );

							foreach ( $topics as $topic_slug => $topic_name ) : ?>
							<option value="<?php echo esc_attr( $topic_slug ); ?>" <?php selected( $topic_data['topic'], $topic_slug, true ); ?>><?php echo esc_html( $topic_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr valign="top" id="webhook-action-event-wrap">
				<th scope="row" class="titledesc">
					<label for="webhook_action_event"><?php _e( 'Action Event', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'Enter the Action that will trigger this webhook.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<input name="webhook_action_event" id="webhook_action_event" type="text" class="input-text regular-input" value="<?php echo esc_attr( $topic_data['event'] ); ?>" />
				</td>
			</tr>
			<tr valign="top" id="webhook-custom-topic-wrap">
				<th scope="row" class="titledesc">
					<label for="webhook_custom_topic"><?php _e( 'Custom Topic', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'Enter the Custom Topic that will trigger this webhook.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<input name="webhook_custom_topic" id="webhook_custom_topic" type="text" class="input-text regular-input" value="<?php echo esc_attr( $webhook->get_topic() ); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="webhook_delivery_url"><?php _e( 'Delivery URL', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'URL where the webhook payload is delivered.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<input name="webhook_delivery_url" id="webhook_delivery_url" type="text" class="input-text regular-input" value="<?php echo esc_attr( $webhook->get_delivery_url() ); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="webhook_secret"><?php _e( 'Secret', 'woocommerce' ); ?></label>
					<img class="help_tip" data-tip="<?php esc_attr_e( 'The Secret Key is used to generate a hash of the delivered webhook and provided in the request headers. This will default to the current API user\'s consumer secret if not provided.', 'woocommerce' ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
				<td class="forminp">
					<input name="webhook_secret" id="webhook_secret" type="text" class="input-text regular-input" value="<?php echo esc_attr( $webhook->get_secret() ); ?>" />
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'woocommerce_webhook_options' ); ?>
</div>

<div id="webhook-actions" class="settings-panel">
	<h3><?php _e( 'Webhook Actions', 'woocommerce' ); ?></h3>
	<table class="form-table">
		<tbody>
			<?php if ( '0000-00-00 00:00:00' != $webhook->post_data->post_modified_gmt ) : ?>
				<?php if ( '0000-00-00 00:00:00' == $webhook->post_data->post_date_gmt ) : ?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<?php _e( 'Created at', 'woocommerce' ); ?>
						</th>
						<td class="forminp">
							<?php echo date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $webhook->post_data->post_modified_gmt ) ); ?>
						</td>
					</tr>
				<?php else : ?>
				<tr valign="top">
						<th scope="row" class="titledesc">
							<?php _e( 'Created at', 'woocommerce' ); ?>
						</th>
						<td class="forminp">
							<?php echo date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $webhook->post_data->post_date_gmt ) ); ?>
						</td>
					</tr>
				<tr valign="top">
						<th scope="row" class="titledesc">
							<?php _e( 'Updated at', 'woocommerce' ); ?>
						</th>
						<td class="forminp">
							<?php echo date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $webhook->post_data->post_modified_gmt ) ); ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endif; ?>
			<tr valign="top">
				<td colspan="2" scope="row" style="padding-left: 0;">
					<p class="submit">
						<input type="submit" class="button button-primary button-large" name="save" id="publish" accesskey="p" value="<?php esc_attr_e( 'Save Webhook', 'woocommerce' ); ?>" />
						<?php if ( current_user_can( 'delete_post', $webhook->id ) ) : ?>
							<a style="color: #a00; text-decoration: none; margin-left: 10px;" href="<?php echo esc_url( get_delete_post_link( $webhook->id ) ); ?>"><?php echo ( ! EMPTY_TRASH_DAYS ) ? __( 'Delete Permanently', 'woocommerce' ) : __( 'Move to Trash', 'woocommerce' ); ?></a>
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div id="webhook-logs" class="settings-panel">
	<h3><?php _e( 'Webhook Logs', 'woocommerce' ); ?></h3>

	<?php WC_Admin_Webhooks::logs_output( $webhook ); ?>
</div>

<script type="text/javascript">
	jQuery( function ( $ ) {
		$( '#webhook-options' ).find( '#webhook_topic' ).on( 'change', function() {
			var current            = $( this ).val(),
				action_event_field = $( '#webhook-options' ).find( '#webhook-action-event-wrap' ),
				custom_topic_field = $( '#webhook-options' ).find( '#webhook-custom-topic-wrap' );

			action_event_field.hide();
			custom_topic_field.hide();

			if ( 'action' === current ) {
				action_event_field.show();
			} else if ( 'custom' === current ) {
				custom_topic_field.show();
			}
		}).change();
	});
</script>
