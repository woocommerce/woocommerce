/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RegionPicker } from './region-picker';

const shippingZoneRegionPickerRoot = document.getElementById(
	'wc-shipping-zone-region-picker-root'
);

const options = JSON.parse( shippingZoneRegionPickerRoot.dataset.options );
const initialValues = JSON.parse( shippingZoneRegionPickerRoot.dataset.values );

if ( shippingZoneRegionPickerRoot ) {
	if ( createRoot ) {
		createRoot( shippingZoneRegionPickerRoot ).render(
			<RegionPicker options={ options } initialValues={ initialValues } />
		);
	} else {
		render(
			<RegionPicker
				options={ options }
				initialValues={ initialValues }
			/>,
			shippingZoneRegionPickerRoot
		);
	}
}
