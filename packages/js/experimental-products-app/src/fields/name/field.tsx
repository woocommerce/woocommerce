/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { type ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'text',
	label: __( 'Name', 'woocommerce' ),
	enableSorting: true,
	filterBy: false,
	enableHiding: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	header: <span>{ __( 'Product', 'woocommerce' ) }</span>,
	isValid: {
		required: true,
	},
};
