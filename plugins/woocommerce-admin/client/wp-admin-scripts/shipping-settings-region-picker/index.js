/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RegionPicker } from './region-picker';
import { ShippingCurrencyContext } from './currency-context';
import { recursivelyTransformLabels, decodeHTMLEntities } from './utils';

const shippingZoneRegionPickerRoot = document.getElementById(
	'wc-shipping-zone-region-picker-root'
);

const options =
	recursivelyTransformLabels(
		window.shippingZoneMethodsLocalizeScript?.region_options,
		decodeHTMLEntities
	) ?? [];
const initialValues = window.shippingZoneMethodsLocalizeScript?.locations ?? [];

const ShippingApp = () => {
	return (
		<div>
			<ShippingCurrencyContext />
			<RegionPicker options={ options } initialValues={ initialValues } />
		</div>
	);
};

if ( shippingZoneRegionPickerRoot ) {
	createRoot( shippingZoneRegionPickerRoot ).render( <ShippingApp /> );
}
