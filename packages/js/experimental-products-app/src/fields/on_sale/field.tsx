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
	type: 'boolean',
	label: __( 'On sale', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	type: 'boolean',
	Edit: 'toggle',
	getValue: ( { item } ) => !! item.on_sale || !! item.sale_price,
	setValue: ( { value } ) =>
		value
			? { on_sale: true }
			: {
					on_sale: false,
					sale_price: '',
					date_on_sale_from: null,
					date_on_sale_to: null,
			  },
};
