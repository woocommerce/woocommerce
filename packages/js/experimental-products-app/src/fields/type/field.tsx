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
	label: __( 'Product type', 'woocommerce' ),
	enableSorting: false,
	filterBy: {
		operators: [ 'isAny', 'isNone' ],
	},
	elements: [
		{ label: __( 'Simple', 'woocommerce' ), value: 'simple' },
		{ label: __( 'Variable', 'woocommerce' ), value: 'variable' },
		{ label: __( 'Grouped', 'woocommerce' ), value: 'grouped' },
		{ label: __( 'Affiliate', 'woocommerce' ), value: 'external' },
		{ label: __( 'Variation', 'woocommerce' ), value: 'variation' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.type,
	render: ( { item }: { item: ProductEntityRecord } ) => {
		const match = fieldDefinition.elements.find(
			( element ) => element.value === item.type
		);
		return match ? match.label : item.type;
	},
};
