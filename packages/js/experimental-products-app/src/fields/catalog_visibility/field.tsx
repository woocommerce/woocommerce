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
	label: __( 'Visibility', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
	elements: [
		{ label: __( 'Public', 'woocommerce' ), value: 'visible' },
		{ label: __( 'Catalog', 'woocommerce' ), value: 'catalog' },
		{ label: __( 'Search', 'woocommerce' ), value: 'search' },
		{ label: __( 'Hidden', 'woocommerce' ), value: 'hidden' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
};
