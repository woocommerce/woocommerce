<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php _e( 'Shipping Methods', 'woocommerce' ); ?> (<?php echo esc_html( $zone->get_zone_name() ); ?>) <small class="wc-admin-breadcrumb"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>" title="<?php echo esc_attr( __( 'Return to Shipping Zones', 'woocommerce' ) ); ?>">&#x21a9;</a></small></h2>
<p><?php _e( 'The following Shipping Methods apply to customers with shipping addresses within this zone.', 'woocommerce' ); ?><p>
<table class="wc-shipping-zone-methods widefat">
    <thead>
        <tr>
            <th class="wc-shipping-zone-method-sort">&nbsp;</th>
            <th class="wc-shipping-zone-method-title"><?php esc_html_e( 'Title', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-method-type"><?php esc_html_e( 'Type', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-method-enabled"><?php esc_html_e( 'Enabled', 'woocommerce' ); ?></th>
            <th class="wc-shipping-zone-method-description"><?php esc_html_e( 'Description', 'woocommerce' ); ?></th>
            <th class="wc-shipping-zone-method-actions">&nbsp;</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="6">
                <div class="wc-shipping-zone-method-selector">
        			<select name="add_method_id">
        				<?php
        					foreach ( $shipping_methods as $method ) {
        						if ( ! $method->supports( 'shipping-zones' ) ) {
        							continue;
                                }
        						echo '<option value="' . esc_attr( $method->id ) . '">' . esc_attr( $method->title ) . '</li>';
        					}
        				?>
        			</select>
        			<input type="submit" class="button wc-shipping-zone-add-method" value="<?php esc_attr_e( 'Add Shipping Method', 'woocommerce' ); ?>" />
        		</div>
                <input type="submit" name="save" class="button button-primary wc-shipping-zone-method-save" value="<?php esc_attr_e( 'Save Shipping Methods', 'woocommerce' ); ?>" disabled />
            </td>
        </tr>
    </tfoot>
    <tbody class="wc-shipping-zone-method-rows"></tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row-blank">
	<tr>
		<td class="wc-shipping-zone-method-blank-state" colspan="6">
			<p class="main"><?php _e( 'Add Shipping Methods to this zone', 'woocommerce' ); ?></p>
			<p><?php _e( 'You can add multiple Shipping Methods within this zone. Only customers within the zone will see them.', 'woocommerce' ); ?></p>
			<p><?php _e( 'Choose a method from the dropdown below and click "Add Shipping Method" to get started.', 'woocommerce' ); ?></p>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row">
    <tr data-id="{{ data.instance_id }}">
        <td width="1%" class="wc-shipping-zone-method-sort"></td>
        <td class="wc-shipping-zone-method-title"><a href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}">{{ data.title }}</a></td>
		<td class="wc-shipping-zone-method-type">{{ data.method_title }}</td>
		<td class="wc-shipping-zone-method-enabled">{{{ data.enabled_icon }}}</td>
        <td class="wc-shipping-zone-method-description">{{ data.method_description }}</td>
        <td class="wc-shipping-zone-method-actions">
			<a class="wc-shipping-zone-method-delete tips" data-tip="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a><a class="wc-shipping-zone-method-settings tips" data-tip="<?php esc_attr_e( 'Settings', 'woocommerce' ); ?>" href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}"><?php _e( 'Settings', 'woocommerce' ); ?></a>
		</td>
    </tr>
</script>
