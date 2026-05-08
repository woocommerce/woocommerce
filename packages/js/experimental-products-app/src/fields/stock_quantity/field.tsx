/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InputControl } from '@wordpress/ui';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'integer',
	label: __( 'Available stock', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isVisible: ( item ) => {
		return !! item.manage_stock;
	},
	Edit: ( { data, onChange, field } ) => {
		return (
			<InputControl
				label={ field.label }
				type="number"
				min={ 0 }
				placeholder={ field.placeholder }
				value={ data.stock_quantity ?? '' }
				onChange={ ( event ) =>
					onChange( {
						stock_quantity: event.target.value
							? Number( event.target.value )
							: undefined,
					} )
				}
			/>
		);
	},
};
