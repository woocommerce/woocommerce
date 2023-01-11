/**
 * External dependencies
 */
import { cleanForSlug } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
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
	enabled: false,
	title: __( 'Local Pickup', 'woo-gutenberg-products-block' ),
	tax_status: 'taxable',
	cost: '',
};

export const defaultReadyOnlySettings = {
	hasLegacyPickup: false,
	storeCountry: '',
};
declare global {
	const hydratedScreenSettings: {
		pickupLocationSettings: {
			enabled: string;
			title: string;
			tax_status: string;
			cost: string;
		};
		pickupLocations: PickupLocation[];
		readonlySettings: typeof defaultReadyOnlySettings;
	};
}

export const getInitialSettings = (): ShippingMethodSettings => {
	const settings = hydratedScreenSettings.pickupLocationSettings;

	return {
		enabled: settings?.enabled
			? settings?.enabled === 'yes'
			: defaultSettings.enabled,
		title: settings?.title || defaultSettings.title,
		tax_status: settings?.tax_status || defaultSettings.tax_status,
		cost: settings?.cost || defaultSettings.cost,
	};
};

export const getInitialPickupLocations = (): SortablePickupLocation[] =>
	indexLocationsById( hydratedScreenSettings.pickupLocations || [] );

export const readOnlySettings =
	hydratedScreenSettings.readonlySettings || defaultReadyOnlySettings;
