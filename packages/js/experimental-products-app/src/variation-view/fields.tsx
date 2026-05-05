/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

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

export const variationFields = REUSED_VARIATION_FIELD_IDS.map(
	( id ) => createProductField( id ) as Field< VariationEntityRecord >
);
