/**
 * External dependencies
 */
import { useEntityRecord } from '@wordpress/core-data';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord, SettingsEntityRecord } from '../types';

const ShippingSummaryRender = ( { item }: { item: ProductEntityRecord } ) => {
	const {
		record: storeProductsSettings,
		isResolving: storeProductsSettingsResolving,
	} = useEntityRecord< SettingsEntityRecord >(
		'root',
		'settings',
		'products'
	);

	if ( storeProductsSettingsResolving ) {
		return null;
	}

	const length = item.dimensions?.length;
	const width = item.dimensions?.width;
	const height = item.dimensions?.height;
	const weight = item.weight;
	const weightUnit = storeProductsSettings?.values?.woocommerce_weight_unit;
	const dimensionUnit =
		storeProductsSettings?.values?.woocommerce_dimension_unit;

	// Return null if no shipping info available
	if ( ! length && ! width && ! height && ! weight ) {
		return null;
	}

	const parts: string[] = [];

	if ( length && width && height ) {
		parts.push(
			`${ length } ${ dimensionUnit } x ${ width } ${ dimensionUnit } x ${ height } ${ dimensionUnit }`
		);
	}

	if ( weight ) {
		parts.push( `${ weight } ${ weightUnit }` );
	}

	return <span>{ parts.join( ' · ' ) }</span>;
};

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => <ShippingSummaryRender item={ item } />,
};
