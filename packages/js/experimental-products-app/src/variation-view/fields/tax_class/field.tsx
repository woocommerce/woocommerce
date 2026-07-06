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

// Radix UI (used by @wordpress/ui SelectControl) rejects empty-string values.
// We use 'parent' as a UI sentinel for the '' API value ("Same as parent").
const SAME_AS_PARENT = 'parent';

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	type: 'text',
	label: __( 'Tax class', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	getValue: ( { item } ) => item.tax_class ?? '',
	Edit: ( { data, onChange, field } ) => {
		const options = [
			{
				value: SAME_AS_PARENT,
				label: __( 'Same as parent', 'woocommerce' ),
			},
			{ value: 'standard', label: __( 'Standard', 'woocommerce' ) },
			{
				value: 'reduced-rate',
				label: __( 'Reduced rate', 'woocommerce' ),
			},
			{ value: 'zero-rate', label: __( 'Zero rate', 'woocommerce' ) },
		];

		const apiValue = data.tax_class ?? '';
		const uiValue = apiValue === '' ? SAME_AS_PARENT : apiValue;
		const selected = options.find( ( o ) => o.value === uiValue );

		return (
			<SelectControl
				label={ field.label }
				value={ selected }
				items={ options }
				onValueChange={ ( option ) => {
					if ( option !== null && option !== undefined ) {
						const apiVal =
							option.value === SAME_AS_PARENT ? '' : option.value;
						onChange( {
							tax_class:
								apiVal as ProductEntityRecord[ 'tax_class' ],
						} );
					}
				} }
			/>
		);
	},
};
