<div class="wrap woocommerce">
	<h1><?php _e( 'Shipping Methods', 'woocommerce' ); ?> (<?php echo esc_html( $zone->get_zone_name() ); ?>) <small class="wc-admin-breadcrumb">&lt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-shipping' ) ); ?>"><?php _e( 'Shipping Zones', 'woocommerce' ); ?></a><small></h1>
    <p><?php printf( __( 'The following shipping methods will apply to customers within the %s zone.', 'woocommerce' ), esc_html( $zone->get_zone_name() ) ); ?><p>
    <form>
        <table class="wc-shipping-zone-methods widefat">
            <thead>
                <tr>
                    <th class="wc-shipping-zone-method-sort">&nbsp;</th>
                    <th class="wc-shipping-zone-method-title"><?php esc_html_e( 'Title', 'woocommerce' ); ?></th>
					<th class="wc-shipping-zone-method-type"><?php esc_html_e( 'Type', 'woocommerce' ); ?></th>
                    <th class="wc-shipping-zone-method-description"><?php esc_html_e( 'Description', 'woocommerce' ); ?></th>
                    <th class="wc-shipping-zone-method-actions">&nbsp;</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="5">
                        <form method="get" class="wc-shipping-zone-method-selector">
                			<select name="method_type">
                				<?php
                					foreach ( $shipping_methods as $method ) {
                						if ( ! $method->supports( 'shipping-zones' ) ) {
                							continue;
                                        }
                						echo '<option value="' . esc_attr( $method->id ) . '">' . esc_attr( $method->title ) . '</li>';
                					}
                				?>
                			</select>
                			<?php wp_nonce_field( 'woocommerce_add_method', '_wpnonce', false ); ?>
                			<input type="hidden" name="add_method" value="true" />
                			<input type="hidden" name="page" value="wc-shipping" />
                			<input type="hidden" name="zone" value="<?php echo absint( $zone_id ); ?>" />
                			<input type="submit" class="button" value="<?php esc_attr_e( 'Add shipping method', 'woocommerce' ); ?>" />
                		</form>
                        <input type="submit" name="save" class="button button-primary wc-shipping-zone-method-save" value="Save shipping methods" disabled />
                    </td>
                </tr>
            </tfoot>
            <tbody class="wc-shipping-zone-method-rows"></tbody>
        </table>
    </form>
</div>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row">
    <tr data-id="{{ data.instance_id }}">
        <td width="1%" class="wc-shipping-zone-method-sort"></td>
        <td class="wc-shipping-zone-method-title"><a href="admin.php?page=wc-shipping&amp;instance_id={{ data.instance_id }}">{{ data.title }}</a></td>
		<td class="wc-shipping-zone-method-type">{{ data.method_title }}</td>
        <td class="wc-shipping-zone-method-description">{{ data.method_description }}</td>
        <td class="wc-shipping-zone-method-actions">
			<a class="wc-shipping-zone-method-delete tips" data-tip="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a><a class="wc-shipping-zone-method-settings tips" data-tip="<?php esc_attr_e( 'Settings', 'woocommerce' ); ?>" href="admin.php?page=wc-shipping&amp;instance_id={{ data.instance_id }}"><?php _e( 'Settings', 'woocommerce' ); ?></a>
		</td>
    </tr>
</script>
