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
import {
	getVariationActiveValue,
	VariationActiveBadge,
} from '../variation_active/field';

function isVariation( item: ProductEntityRecord ) {
	return item.type === 'variation' || Boolean( item.parent_id );
}

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
	getValue: ( { item } ) =>
		isVariation( item ) ? getVariationActiveValue( item ) : item.status,
	render: ( { item }: { item: ProductEntityRecord } ) =>
		isVariation( item ) ? (
			<VariationActiveBadge value={ getVariationActiveValue( item ) } />
		) : (
			<ProductStatusBadge status={ item.status } />
		),
	Edit: ( { data, onChange, field } ) => {
		const options =
			field.elements?.filter(
				( element: { label: string; value: string } ) =>
					element.value !== 'trash'
			) ?? [];
		const selectedOption =
			field.placeholder && ! data.status
				? undefined
				: options.find( ( option ) => option.value === data.status );

		return (
			<SelectControl
				label={ field.label }
				placeholder={ field.placeholder }
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
