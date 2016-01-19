<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php _e( 'Shipping Zones', 'woocommerce' ); ?></h2>
<p><?php _e( 'Shipping Zones let you group regions with similar Shipping Methods and rates. WooCommerce will automatically choose the correct Shipping Zone based on your customer&lsquo;s shipping address and present the Shipping Methods within that zone to them. If there are no Shipping Methods within the matching zone, the customer will see a notice stating that there are no shipping methods available for the address they&lsquo;ve provided.', 'woocommerce' ); ?><p>

<table class="wc-shipping-zones widefat">
    <thead>
        <tr>
            <th class="wc-shipping-zone-sort">&nbsp;</th>
            <th class="wc-shipping-zone-name"><?php esc_html_e( 'Zone Name', 'woocommerce' ); ?></th>
            <th class="wc-shipping-zone-region"><?php esc_html_e( 'Region(s)', 'woocommerce' ); ?></th>
            <th class="wc-shipping-zone-methods"><?php esc_html_e( 'Shipping Method(s)', 'woocommerce' ); ?></th>
            <th class="wc-shipping-zone-actions">&nbsp;</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="5">
                <a class="button button-secondary wc-shipping-zone-add" href="#"><?php esc_html_e( 'Add Shipping Zone', 'woocommerce' ); ?></a>
                <input type="submit" name="save" class="button button-primary wc-shipping-zone-save" value="<?php esc_attr_e( 'Save Shipping Zones', 'woocommerce' ); ?>" disabled />
            </td>
        </tr>
    </tfoot>
    <tbody class="wc-shipping-zone-rows"></tbody>
    <tbody>
        <tr data-id="0">
            <td width="1%" class="wc-shipping-zone-worldwide"></td>
            <td class="wc-shipping-zone-name">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=0' ) ); ?>"><?php esc_html_e( 'Rest of the World', 'woocommerce' ); ?></a>
                <div class="row-actions">
                    <a href="admin.php?page=wc-settings&amp;tab=shipping&amp;zone_id={{ data.zone_id }}"><?php _e( 'Edit Shipping Methods', 'woocommerce' ); ?></a>
                </div>
            </td>
            <td class="wc-shipping-zone-region"><?php esc_html_e( 'Shipping Methods added here will apply to shipping addresses that aren&lsquo;t included in any other Shipping Zone.', 'woocommerce' ); ?></td>
            <td class="wc-shipping-zone-methods">
				<?php
					$worldwide = new WC_Shipping_Zone( 0 );
					$methods   = $worldwide->get_shipping_methods();

					if ( ! $methods ) {
						echo '<ul><li><a href="#" class="add_shipping_method button">' . __( 'Add Shipping Method', 'woocommerce' ) . '</a></li></ul>';
					} else {
						echo '<ul>';
						foreach ( $methods as $method ) {
                            $class_name = 'yes' === $method->enabled ? 'method_enabled' : 'method_disabled';
							echo '<li><a href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id=' . absint( $method->instance_id ) . '" class="' . esc_attr( $class_name ) . '">' . esc_html( $method->get_title() ) . '</a></li>';
						}
                        echo '<a href="#" class="add_shipping_method button">' . __( 'Add Shipping Method', 'woocommerce' ) . '</a>';
						echo '</ul>';
					}
				?>
			</td>
            <td class="wc-shipping-zone-actions"></td>
        </tr>
    </tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-zone-row-blank">
	<tr>
		<td class="wc-shipping-zones-blank-state" colspan="5">
			<p class="main"><?php _e( 'Shipping Zones let you group regions with similar Shipping Methods and rates.', 'woocommerce' ); ?></p>
			<p><?php _e( 'You can add as many Shipping Zones as you want, for example you could have "Local", "Domestic", and "Europe" zones.', 'woocommerce' ); ?></p>
			<p><?php _e( 'Once a Shipping Zone has been added, you can add one or more Shipping Methods to it. Customers will only see Shipping Methods that apply to their address.', 'woocommerce' ); ?></p>
			<a class="button button-primary wc-shipping-zone-add"><?php _e( 'Add Shipping Zone', 'woocommerce' ); ?></a>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-zone-row">
    <tr data-id="{{ data.zone_id }}">
        <td width="1%" class="wc-shipping-zone-sort"></td>
        <td class="wc-shipping-zone-name">
            <div class="view">
                <a href="admin.php?page=wc-settings&amp;tab=shipping&amp;zone_id={{ data.zone_id }}">{{ data.zone_name }}</a>
                <div class="row-actions">
                    <a class="wc-shipping-zone-edit" href="#"><?php _e( 'Edit Zone', 'woocommerce' ); ?></a> | <a href="admin.php?page=wc-settings&amp;tab=shipping&amp;zone_id={{ data.zone_id }}"><?php _e( 'Edit Shipping Methods', 'woocommerce' ); ?></a>
                </div>
            </div>
            <div class="edit"><input type="text" name="zone_name[{{ data.zone_id }}]" data-attribute="zone_name" value="{{ data.zone_name }}" placeholder="<?php esc_attr_e( 'Zone Name', 'woocommerce' ); ?>" /></div>
        </td>
		<td class="wc-shipping-zone-region">
			<div class="view">{{ data.formatted_zone_location }}</div>
			<div class="edit">
				<select multiple="multiple" name="zone_locations[{{ data.zone_id }}]" data-attribute="zone_locations" data-placeholder="<?php _e( 'Select regions within this zone', 'woocommerce' ); ?>" class="wc-shipping-zone-region-select">
					<?php
						foreach ( $continents as $continent_code => $continent ) {
							echo '<option value="continent:' . esc_attr( $continent_code ) . '" alt="">' . esc_html( $continent['name'] ) . '</option>';

							$countries = array_intersect( array_keys( $allowed_countries ), $continent['countries'] );

							foreach ( $countries as $country_code ) {
								echo '<option value="country:' . esc_attr( $country_code ) . '" alt="' . esc_attr( $continent['name'] ) . '">' . esc_html( '&nbsp;&nbsp; ' . $allowed_countries[ $country_code ] ) . '</option>';

								if ( $states = WC()->countries->get_states( $country_code ) ) {
									foreach ( $states as $state_code => $state_name ) {
										echo '<option value="state:' . esc_attr( $country_code . ':' . $state_code ) . '" alt="' . esc_attr( $continent['name'] . ' ' . $allowed_countries[ $country_code ] ) . '">' . esc_html( '&nbsp;&nbsp;&nbsp;&nbsp; ' . $state_name ) . '</option>';
									}
								}
							}
						}
					?>
				</select>
				<a class="wc-shipping-zone-postcodes-toggle" href="#"><?php _e( 'Limit to specific ZIP/postcodes', 'woocommerce' ); ?></a>
				<div class="wc-shipping-zone-postcodes">
					<textarea name="zone_postcodes[{{ data.zone_id }}]" data-attribute="zone_postcodes" placeholder="<?php esc_attr_e( 'List 1 postcode per line', 'woocommerce' ); ?>" class="input-text large-text" cols="25" rows="5"></textarea>
					<span class="description"><?php _e( 'Wildcards and numerical ranges are supported too, for example, 90210-99000 and CB23*', 'woocommerce' ) ?></span>
				</div>
			</div>
		</td>
        <td class="wc-shipping-zone-methods">
			<div class="view">
                <ul></ul>
            </div>
            <div class="edit"><?php _e( '<a href="#" class="wc-shipping-zone-save-changes">Save changes</a> before adding methods.', 'woocommerce' ); ?></div>
		</td>
        <td class="wc-shipping-zone-actions">
			<a class="wc-shipping-zone-delete tips" data-tip="<?php _e( 'Delete', 'woocommerce' ); ?>" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a>
		</td>
    </tr>
</script>

<script type="text/template" id="tmpl-wc-modal-add-shipping-method">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php _e( 'Add Shipping Method', 'woocommerce' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
					<form action="" method="post">
                        <div class="wc-shipping-zone-method-selector">
                			<select name="add_method_id">
                				<?php
                					foreach ( WC()->shipping->load_shipping_methods() as $method ) {
                						if ( ! $method->supports( 'shipping-zones' ) ) {
                							continue;
                                        }
                						echo '<option value="' . esc_attr( $method->id ) . '">' . esc_attr( $method->title ) . '</li>';
                					}
                				?>
                			</select>
                            <input type="hidden" name="zone_id" value="{{{ data.zone_id }}}" />
                		</div>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Add Shipping Method', 'woocommerce' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
