<div class="wrap woocommerce">
	<h1><?php _e( 'Shipping Zones', 'woocommerce' ); ?></h1>
    <p><?php _e( 'Create shipping zones for all of the places you ship products to. Customers who enter a shipping address that isn\'t included in your shipping zones will receive a notice that there is no shipping available to their region.', 'woocommerce' ); ?><p>
    <form>
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
                        <a class="button button-secondary wc-shipping-zone-add" href="#"><?php esc_html_e( 'Add shipping zone', 'woocommerce' ); ?></a>
                        <input type="submit" name="save" class="button button-primary wc-shipping-zone-save" value="Save shipping zones" disabled />
                    </td>
                </tr>
            </tfoot>
            <tbody class="wc-shipping-zone-rows">
				<tr>
					<td class="wc-shipping-zones-blank-state" colspan="5">
						<p class="main"><?php _e( 'Shipping Zones are regions you ship products to.', 'woocommerce' ); ?></p>
						<p><?php _e( 'You can add as many zones as you want, for example you could have "Local", "Domestic", and "Europe" zones depending on your needs.', 'woocommerce' ); ?></p>
						<p><?php _e( 'Once a zone has been added, you can add multiple shipping rates within each. Customers will only see rates that apply to them.', 'woocommerce' ); ?></p>
						<a class="button button-primary wc-shipping-zone-add"><?php _e( 'Add a shipping zone', 'woocommerce' ); ?></a>
					</td>
				</tr>
			</tbody>
            <tbody>
                <tr>
                    <td width="1%" class="wc-shipping-zone-worldwide"></td>
                    <td class="wc-shipping-zone-name"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-shipping&zone_id=0' ) ); ?>"><?php esc_html_e( 'Worldwide', 'woocommerce' ); ?></a></td>
                    <td class="wc-shipping-zone-region"><?php esc_html_e( 'Shipping methods added here apply to all regions without a zone.', 'woocommerce' ); ?></td>
                    <td class="wc-shipping-zone-methods">
						<?php
							$worldwide = new WC_Shipping_Zone( 0 );
							$methods   = $worldwide->get_shipping_methods();

							if ( ! $methods ) {
								echo '&ndash;';
							} else {
								echo '<ul>';
								foreach ( $methods as $method ) {
									echo '<li><a href="admin.php?page=wc-shipping&amp;instance_id=' . absint( $method->instance_id ) . '">' . esc_html( $method->get_title() ) . '</a></li>';
								}
								echo '</ul>';
							}
						?>
					</td>
                    <td class="wc-shipping-zone-actions"><a class="wc-shipping-zone-view tips" data-tip="<?php _e( 'View Zone', 'woocommerce' ); ?>" href="admin.php?page=wc-shipping&amp;zone_id=0"><?php _e( 'View', 'woocommerce' ); ?></a></td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<script type="text/html" id="tmpl-wc-shipping-zone-row">
    <tr data-id="{{ data.zone_id }}">
        <td width="1%" class="wc-shipping-zone-sort"></td>
        <td class="wc-shipping-zone-name">
            <div class="view"><a href="admin.php?page=wc-shipping&amp;zone_id={{ data.zone_id }}">{{ data.zone_name }}</a></div>
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
			<ul></ul>
		</td>
        <td class="wc-shipping-zone-actions">
			<a class="wc-shipping-zone-delete tips" data-tip="<?php _e( 'Delete', 'woocommerce' ); ?>" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a><a class="wc-shipping-zone-edit tips" href="#" data-tip="<?php _e( 'Edit', 'woocommerce' ); ?>"><?php _e( 'Edit', 'woocommerce' ); ?></a><a class="wc-shipping-zone-view tips" data-tip="<?php _e( 'View Zone', 'woocommerce' ); ?>" href="admin.php?page=wc-shipping&amp;zone_id={{ data.zone_id }}"><?php _e( 'View', 'woocommerce' ); ?></a>
		</td>
    </tr>
</script>
