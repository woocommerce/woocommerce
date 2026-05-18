/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { createProductField, type ProductFieldId } from '../fields/registry';
import type { VariationEntityRecord } from './types';

const REUSED_VARIATION_FIELD_IDS = [
	'name',
	'sku',
	'price',
	'regular_price',
	'sale_price',
	'stock',
	'stock_quantity',
	'manage_stock',
	'product_status',
	'images',
	'downloadable',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'tax_status',
] as const satisfies readonly ProductFieldId[];

function variationNameRender( { item }: { item: VariationEntityRecord } ) {
	return (
		<div className="woocommerce-variation-view__name-cell">
			<span className="woocommerce-variation-view__name-cell-title">
				{ item.name }
			</span>
			<span className="woocommerce-variation-view__name-cell-id">
				{ `#${ item.id }` }
			</span>
		</div>
	);
}

export const variationFields = REUSED_VARIATION_FIELD_IDS.map( ( id ) => {
	const field = createProductField( id ) as Field< VariationEntityRecord >;

	if ( id === 'name' ) {
		return {
			...field,
			header: <span>{ __( 'Variation', 'woocommerce' ) }</span>,
			render: variationNameRender,
			enableGlobalSearch: true,
		};
	}

	if ( id === 'sku' ) {
		return {
			...field,
			enableGlobalSearch: true,
		};
	}

	return field;
} );
