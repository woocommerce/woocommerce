/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	type: 'boolean',
	label: __( 'Virtual', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
	getValue: ( { item } ) => item.virtual ?? false,
	Edit: 'checkbox',
};
