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

import { SelectControl } from '../components/compat-controls';

function isValidStatus( value: string ) {
	return value === 'draft' || value === 'publish' || value === 'trash';
}

const fieldDefinition = {
	type: 'text',
	label: __( 'Status', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	elements: [
		{ value: 'draft', label: __( 'Draft', 'woocommerce' ) },
		{ value: 'publish', label: __( 'Active', 'woocommerce' ) },
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
		const description =
			data.status === 'publish'
				? __(
						'This product is live and visible on your store.',
						'woocommerce'
				  )
				: __(
						'This product is not visible to customers.',
						'woocommerce'
				  );

		return (
			<SelectControl
				label={ field.label }
				description={ description }
				value={ data.status }
				items={ field.elements?.filter(
					( element: { label: string; value: string } ) =>
						element.value !== 'trash'
				) }
				onValueChange={ ( value ) => {
					if ( value && isValidStatus( value ) ) {
						onChange( {
							status: value,
						} );
					}
				} }
			/>
		);
	},
};
