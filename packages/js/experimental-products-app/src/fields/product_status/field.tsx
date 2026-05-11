/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { ProductStatusBadge } from '../components/product-status-badge';
import { SelectField } from '../components/select-field';

function isValidStatus( value: string ) {
	return (
		value === 'draft' ||
		value === 'pending' ||
		value === 'publish' ||
		value === 'trash'
	);
}

const fieldDefinition = {
	type: 'text',
	label: __( 'Status', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	elements: [
		{ value: 'publish', label: __( 'Published', 'woocommerce' ) },
		{ value: 'draft', label: __( 'Draft', 'woocommerce' ) },
		{ value: 'pending', label: __( 'Pending review', 'woocommerce' ) },
		{ value: 'trash', label: __( 'Trash', 'woocommerce' ) },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.status,
	render: ( { item }: { item: ProductEntityRecord } ) => (
		<ProductStatusBadge status={ item.status } />
	),
	Edit: ( { data, onChange, field } ) => (
		<SelectField
			label={ field.label }
			value={ data.status }
			options={
				field.elements?.filter(
					( element: { label: string; value: string } ) =>
						element.value !== 'trash'
				) ?? []
			}
			onChange={ ( value ) => {
				if ( value && isValidStatus( value ) ) {
					onChange( {
						status: value,
					} );
				}
			} }
		/>
	),
};
