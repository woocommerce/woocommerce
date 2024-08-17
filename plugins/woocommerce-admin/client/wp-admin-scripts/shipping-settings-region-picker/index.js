/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { RegionPicker } from './region-picker';
import { ShippingCurrencyContext } from './currency-context';
import { recursivelyTransformLabels } from './utils';

const shippingZoneRegionPickerRoot = document.getElementById(
	'wc-shipping-zone-region-picker-root'
);

const options =
	recursivelyTransformLabels(
		window.shippingZoneMethodsLocalizeScript?.region_options,
		decodeEntities
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
