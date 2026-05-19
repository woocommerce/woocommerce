/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	type: 'text',
	label: __( 'Tax class', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	getValue: ( { item } ) => item.tax_class ?? '',
	Edit: ( { data, onChange, field } ) => {
		const options = [
			{ value: '', label: __( 'Same as parent', 'woocommerce' ) },
			{ value: 'standard', label: __( 'Standard', 'woocommerce' ) },
			{
				value: 'reduced-rate',
				label: __( 'Reduced rate', 'woocommerce' ),
			},
			{ value: 'zero-rate', label: __( 'Zero rate', 'woocommerce' ) },
		];
		const selected = options.find(
			( o ) => o.value === ( data.tax_class ?? '' )
		);
		return (
			<SelectControl
				label={ field.label }
				value={ selected }
				items={ options }
				onValueChange={ ( option ) => {
					if ( option != null ) {
						onChange( {
							tax_class: option.value as ProductEntityRecord[ 'tax_class' ],
						} );
					}
				} }
			/>
		);
	},
};
