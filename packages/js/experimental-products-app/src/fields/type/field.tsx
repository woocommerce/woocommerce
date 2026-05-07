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
	enableHiding: false,
	filterBy: {
		operators: [ 'isAny', 'isNone' ],
	},
	elements: [
		{ label: __( 'Simple', 'woocommerce' ), value: 'simple' },
		{ label: __( 'Variable', 'woocommerce' ), value: 'variable' },
		{ label: __( 'Grouped', 'woocommerce' ), value: 'grouped' },
		{ label: __( 'External', 'woocommerce' ), value: 'external' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.type,
};
