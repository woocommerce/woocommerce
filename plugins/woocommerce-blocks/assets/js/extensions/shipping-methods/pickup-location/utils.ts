/**
 * External dependencies
 */
import { cleanForSlug } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type {
	PickupLocation,
	SortablePickupLocation,
	ShippingMethodSettings,
} from './types';

export const indexLocationsById = (
	locations: PickupLocation[]
): SortablePickupLocation[] => {
	return locations.map( ( value, index ) => {
		return {
			...value,
			id: cleanForSlug( value.name ) + '-' + index,
		};
	} );
};

export const defaultSettings = {
	enabled: 'yes',
	title: '',
	tax_status: 'taxable',
	cost: '',
};

export const getInitialSettings = (): ShippingMethodSettings => {
	const settings = getSetting(
		'pickupLocationSettings',
		defaultSettings
	) as typeof defaultSettings;

	return {
		enabled: settings?.enabled === 'yes',
		title: settings?.title || defaultSettings.title,
		tax_status: settings?.tax_status || defaultSettings.tax_status,
		cost: settings?.cost || defaultSettings.cost,
	};
};

export const getInitialPickupLocations = (): SortablePickupLocation[] =>
	indexLocationsById( getSetting( 'pickupLocations', [] ) );
