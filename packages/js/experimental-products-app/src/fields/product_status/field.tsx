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
import { ProductStatusBadge } from '../components/product-status-badge';

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
	Edit: ( { data, onChange, field } ) => {
		const options =
			field.elements?.filter(
				( element: { label: string; value: string } ) =>
					element.value !== 'trash'
			) ?? [];
		const selectedOption = options.find(
			( option ) => option.value === data.status
		);

		return (
			<SelectControl
				label={ field.label }
				value={ selectedOption }
				items={ options }
				onValueChange={ ( option ) => {
					const value = option?.value;

					if ( typeof value === 'string' && isValidStatus( value ) ) {
						onChange( {
							status: value,
						} );
					}
				} }
			/>
		);
	},
};
