/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'text',
	label: __( 'GTIN, UPC, EAN, ISBN', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.global_unique_id ?? '',
	render: ( { item } ) => {
		const value = item.global_unique_id;

		if ( ! value ) {
			return <span>{ '—' }</span>;
		}

		return <span>{ value }</span>;
	},
};
