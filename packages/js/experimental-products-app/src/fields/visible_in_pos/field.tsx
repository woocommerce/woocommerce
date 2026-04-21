/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const POS_SUPPORTED_TYPES: readonly string[] = [ 'simple', 'variable' ];

const fieldDefinition = {
	type: 'boolean',
	label: __( 'Point of sale', 'woocommerce' ),
	description: __(
		'Controls whether this product also appears in the point of sale.',
		'woocommerce'
	),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	type: 'boolean',
	Edit: 'toggle',
	getValue: ( { item } ) => item.visible_in_pos ?? true,
	setValue: ( { value } ) => ( {
		visible_in_pos: !! value,
	} ),
	isVisible: ( item ) =>
		POS_SUPPORTED_TYPES.includes( item.type ) && ! item.downloadable,
};
