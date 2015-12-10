<div class="wrap woocommerce">
	<h2><?php _e( 'Shipping Zones', 'woocommerce' ); ?></h2>
    <p><?php _e( 'Create shipping zones for all of the places you ship products to. Customers who enter a shipping address that isn\'t included in your shipping zones will receive a notice that there is no shipping available to their region.', 'woocommerce' ); ?><p>

    <form>
        <table class="wc_shipping_zones widefat">
            <thead>
                <tr>
                    <th class="wc-shipping-zone-sort">&nbsp;</th>
                    <th class="wc-shipping-zone-name">Zone Name</th>
                    <th class="wc-shipping-zone-region">Region(s)</th>
                    <th class="wc-shipping-zone-methods">Shipping Method(s)</th>
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
            <tbody class="wc-shipping-zone-rows"></tbody>
            <tbody>
                <tr>
                    <td width="1%" class="wc-shipping-zone-worldwide"></td>
                    <td class="wc-shipping-zone-name">
                        <?php esc_html_e( 'Worldwide', 'woocommerce' ); ?>
                    </td>
                    <td class="wc-shipping-zone-region"><?php esc_html_e( 'Shipping methods added here apply to all regions without a zone.', 'woocommerce' ); ?></td>
                    <td>
                        <a href="#" class="wc-shipping-zone-add-method button"><?php esc_html_e( 'Add a shipping method', 'woocommerce' ); ?></a>
                    </td>
                    <td class="wc-shipping-zone-actions"></td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<script type="text/html" id="tmpl-wc-shipping-zone-row">
    <tr data-id="{{ data.zone_id }}">
        <td width="1%" class="wc-shipping-zone-sort"></td>
        <td class="wc-shipping-zone-name">
            <div class="view">{{ data.zone_name }}</div>
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
				<a class="wc-shipping-zone-postcodes" href="#"><?php _e( 'Limit to specific ZIP/postcodes', 'woocommerce' ); ?></a>
			</div>
		</td>
        <td class="wc-shipping-zone-methods"><a class="wc-shipping-zone-add-method button" href="#"><?php esc_html_e( 'Add a shipping method', 'woocommerce' ); ?></a></td>
        <td class="wc-shipping-zone-actions">
			<a class="wc-shipping-zone-delete" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a><a class="wc-shipping-zone-edit" href="#"><?php _e( 'Edit', 'woocommerce' ); ?></a>
		</td>
    </tr>
</script>
