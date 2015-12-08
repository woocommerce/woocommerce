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
                    <th class="wc-shipping-zone-remove">&nbsp;</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="5">
                        <a class="button button-secondary" href="#"><?php esc_html_e( 'Add shipping zone', 'woocommerce' ); ?></a>
                        <input type="submit" class="button button-primary" value="Save shipping zones" />
                    </td>
                </tr>
            </tfoot>
            <tbody>
                <tr>
                    <td width="1%" class="wc-shipping-zone-sort">
    					<input type="hidden" name="zone_order[]" value="">
    				</td>
                    <td class="wc-shipping-zone-name">
                        UK and Eire
                    </td>
                    <td>UK, Ireland</td>
                    <td>
                        <a href="#"><?php esc_html_e( 'Add a shipping method', 'woocommerce' ); ?></a>
                    </td>
                    <td width="1%"><a class="delete" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a></td>
                </tr>
                <tr>
                    <td width="1%" class="wc-shipping-zone-sort">
    					<input type="hidden" name="zone_order[]" value="">
    				</td>
                    <td class="wc-shipping-zone-name">
                        <input type="text" name="" value="" placeholder="<?php esc_attr_e( 'Zone Name', 'woocommerce' ); ?>" />
                    </td>
                    <td class="wc-shipping-zone-region">
                        <select multiple="multiple" name="" data-placeholder="<?php _e( 'Select countries inside this zone', 'woocommerce' ); ?>"  class="wc-enhanced-select">
                       		<?php
                       			foreach ( $continents as $key => $continent ) {
                                    echo '<optgroup label="' . esc_attr( $continent['name'] ) . '">';

                                    $countries = array_intersect( array_keys( $allowed_countries ), $continent['countries'] );

                                    foreach ( $countries as $key ) {
                                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $allowed_countries[ $key ] ) . '</option>';
                                    }

                                    echo '</optgroup>';
                        		}
                       		?>
                    	</select>
                        <a href="">Limit to specific states</a> | <a href="">Limit to specific zip codes</a>
                    </td>
                    <td></td>
                    <td width="1%"><a class="delete" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a></td>
                </tr>
            </tbody>
            <tbody>
                <tr>
                    <td width="1%" class="wc-shipping-zone-worldwide"></td>
                    <td class="wc-shipping-zone-name">
                        <?php esc_html_e( 'Worldwide', 'woocommerce' ); ?>
                    </td>
                    <td class="wc-shipping-zone-region"><?php esc_html_e( 'Shipping methods added here apply to all regions without a zone.', 'woocommerce' ); ?></td>
                    <td>
                        <a href="#"><?php esc_html_e( 'Add a shipping method', 'woocommerce' ); ?></a>
                    </td>
                    <td width="1%"><a class="delete" href="#"><?php _e( 'Delete', 'woocommerce' ); ?></a></td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
