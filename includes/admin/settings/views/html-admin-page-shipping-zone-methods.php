<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php _e( 'Shipping Methods', 'woocommerce' ); ?> (<?php echo esc_html( $zone->get_zone_name() ); ?>) <?php echo wc_back_link( __( 'Return to Shipping Zones', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?></h2>
<p><?php _e( 'The following shipping methods apply to customers with shipping addresses within this zone.', 'woocommerce' ); ?><p>
<table class="wc-shipping-zone-methods widefat">
    <thead>
        <tr>
            <th class="wc-shipping-zone-method-sort"><?php echo wc_help_tip( __( 'Drag and drop to re-order your shipping methods. This is the order in which they will display during checkout.', 'woocommerce' ) ); ?></th>
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
        			<input type="submit" class="button wc-shipping-zone-add-method" value="<?php esc_attr_e( 'Add shipping method', 'woocommerce' ); ?>" />
        		</div>
                <input type="submit" name="save" class="button button-primary wc-shipping-zone-method-save" value="<?php esc_attr_e( 'Save shipping methods', 'woocommerce' ); ?>" disabled />
            </td>
        </tr>
    </tfoot>
    <tbody class="wc-shipping-zone-method-rows"></tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row-blank">
	<tr>
		<td class="wc-shipping-zone-method-blank-state" colspan="6">
			<p class="main"><?php _e( 'Add shipping methods to this zone', 'woocommerce' ); ?></p>
			<p><?php _e( 'You can add multiple shipping methods within this zone. Only customers within the zone will see them.', 'woocommerce' ); ?></p>
			<p><?php _e( 'Choose a method from the dropdown below and click "Add shipping method" to get started.', 'woocommerce' ); ?></p>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row">
    <tr data-id="{{ data.instance_id }}" data-enabled="{{ data.enabled }}">
        <td width="1%" class="wc-shipping-zone-method-sort"></td>
        <td class="wc-shipping-zone-method-title"><a href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}">{{ data.title }}</a></td>
		<td class="wc-shipping-zone-method-type">{{ data.method_title }}</td>
		<td class="wc-shipping-zone-method-enabled"><a href="#">{{{ data.enabled_icon }}}</a></td>
        <td class="wc-shipping-zone-method-description">{{ data.method_description }}</td>
        <td class="wc-shipping-zone-method-actions">
			<a class="wc-shipping-zone-method-delete tips" data-tip="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a><a class="wc-shipping-zone-method-settings tips" data-tip="<?php esc_attr_e( 'Settings', 'woocommerce' ); ?>" href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}"><?php _e( 'Settings', 'woocommerce' ); ?></a>
		</td>
    </tr>
</script>

<script type="text/template" id="tmpl-wc-modal-shipping-method-settings">
	<div class="wc-backbone-modal wc-backbone-modal-shipping-method-settings">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html( sprintf( __( '%s Settings', 'Shipping Method Settings', 'woocommerce' ), '{{{ data.method.method_title }}}' ) ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article class="wc-modal-shipping-method-settings">
					<form action="" method="post">
						{{{ data.method.settings_html }}}
						<input type="hidden" name="instance_id" value="{{{ data.instance_id }}}" />
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Save changes', 'woocommerce' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
