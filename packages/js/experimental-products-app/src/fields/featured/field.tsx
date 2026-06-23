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
	label: __( 'Featured product', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	type: 'boolean',
	Edit: 'checkbox',
	getValue: ( { item } ) => !! item.featured,
	setValue: ( { value } ) => ( {
		featured: !! value,
	} ),
};
