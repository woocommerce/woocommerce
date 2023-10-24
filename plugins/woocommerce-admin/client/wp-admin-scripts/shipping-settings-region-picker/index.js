/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RegionPicker } from './region-picker';
import { ShippingCurrencyContext } from './currency-context';

const shippingZoneRegionPickerRoot = document.getElementById(
	'wc-shipping-zone-region-picker-root'
);

const options = window.shippingZoneMethodsLocalizeScript?.region_options ?? [];
const initialValues = window.shippingZoneMethodsLocalizeScript?.locations ?? [];

const ShippingApp = () => (
	<div>
		<ShippingCurrencyContext />
		<RegionPicker options={ options } initialValues={ initialValues } />
	</div>
);

if ( shippingZoneRegionPickerRoot ) {
	if ( createRoot ) {
		createRoot( shippingZoneRegionPickerRoot ).render( <ShippingApp /> );
	} else {
		render( <ShippingApp />, shippingZoneRegionPickerRoot );
	}
}
