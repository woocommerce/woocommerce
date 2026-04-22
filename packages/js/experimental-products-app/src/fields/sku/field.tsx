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
	label: __( 'SKU', 'woocommerce' ),
	description: __(
		'Give your product a unique code to help identify it in orders and fulfilment.',
		'woocommerce'
	),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => {
		const sku = item.sku;

		if ( sku === undefined || sku === null || sku === '' ) {
			return <span>{ '\u2014' }</span>;
		}

		return <span>{ sku }</span>;
	},
};
