/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { type ProductEntityRecord } from '../types';

import { CurrencyInput } from '../components/currency-input';

import { validatePrice } from '../price/utils';

const fieldDefinition = {
	type: 'text',
	label: __( 'Regular Price', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isValid: {
		custom: ( item ) => validatePrice( item.regular_price ),
	},
	Edit: CurrencyInput,
};
