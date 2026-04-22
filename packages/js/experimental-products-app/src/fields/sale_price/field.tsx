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

import { validateSalePrice } from './validation';

const fieldDefinition = {
	type: 'text',
	label: __( 'Sale Price', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isVisible: ( item ) => {
		return !! item.on_sale || !! item.sale_price;
	},
	isValid: {
		custom: ( item ) => validateSalePrice( item ),
	},
	Edit: CurrencyInput,
};
