/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	type: 'integer',
	label: __( 'Low stock threshold', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	isVisible: ( item ) => !! item.manage_stock,
	getValue: ( { item } ) => item.low_stock_amount ?? '',
	Edit: ( { data, onChange, field } ) => {
		const value = data.low_stock_amount;
		return (
			<TextControl
				__nextHasNoMarginBottom
				label={ field.label }
				type="number"
				min={ 0 }
				value={
					value === null || value === undefined ? '' : String( value )
				}
				placeholder={ __( 'Store wide threshold', 'woocommerce' ) }
				onChange={ ( next ) => {
					const parsed =
						next === '' ? undefined : parseInt( next, 10 );
					onChange( {
						low_stock_amount: Number.isNaN( parsed )
							? undefined
							: parsed,
					} );
				} }
			/>
		);
	},
};
