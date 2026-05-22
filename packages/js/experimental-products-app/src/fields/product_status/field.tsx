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

type VariationStatus = 'publish' | 'private';

const MIXED_VALUE = '__mixed__';

function isValidStatus( value: string ) {
	return (
		value === 'draft' ||
		value === 'pending' ||
		value === 'publish' ||
		value === 'trash'
	);
}

function isValidVariationStatus( value: string ): value is VariationStatus {
	return value === 'publish' || value === 'private';
}

function isVariationItem( item: Partial< ProductEntityRecord > ) {
	return item.type === 'variation' || Boolean( item.parent_id );
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

const variationStatusElements = [
	{ value: 'publish', label: __( 'Active', 'woocommerce' ) },
	{ value: 'private', label: __( 'Inactive', 'woocommerce' ) },
];

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
		if ( isVariationItem( data ) ) {
			const isMixed = ! data.status;
			const mixedOption = {
				value: MIXED_VALUE,
				label: __( '(Mixed)', 'woocommerce' ),
			};
			const items = isMixed
				? [ mixedOption, ...variationStatusElements ]
				: variationStatusElements;
			const selectedOption = isMixed
				? mixedOption
				: variationStatusElements.find(
						( option ) => option.value === data.status
				  );

			const variationDescriptions: Record< string, string > = {
				publish: __(
					'Visible on your storefront and available for purchase.',
					'woocommerce'
				),
				private: __(
					'Hidden from your storefront and unavailable for purchase.',
					'woocommerce'
				),
			};

			return (
				<SelectControl
					label={ field.label }
					value={ selectedOption }
					items={ items }
					onValueChange={ ( option ) => {
						const value = option?.value;

						if (
							typeof value === 'string' &&
							isValidVariationStatus( value )
						) {
							onChange( { status: value } );
						}
					} }
				>
					{ items.map( ( item ) => (
						<SelectControl.Item
							key={ item.value ?? 'mixed' }
							value={ item }
							label={ item.label }
						>
							<span className="woocommerce-variation-status-option">
								<span>{ item.label }</span>
								{ item.value !== MIXED_VALUE &&
									variationDescriptions[
										item.value ?? ''
									] && (
										<span className="woocommerce-variation-status-option__description">
											{
												variationDescriptions[
													item.value ?? ''
												]
											}
										</span>
									) }
							</span>
						</SelectControl.Item>
					) ) }
				</SelectControl>
			);
		}

		const baseOptions =
			field.elements?.filter(
				( element: { label: string; value: string } ) =>
					element.value !== 'trash'
			) ?? [];
		const isMixed = ! data.status;
		const mixedOption = {
			value: MIXED_VALUE,
			label: __( '(Mixed)', 'woocommerce' ),
		};
		const options = isMixed ? [ mixedOption, ...baseOptions ] : baseOptions;
		const selectedOption = isMixed
			? mixedOption
			: baseOptions.find( ( option ) => option.value === data.status );

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
