/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from './types';
import { fieldExtensions as allowBackordersFieldExtensions } from './allow_backorders/field';
import { fieldExtensions as attributesFieldExtensions } from './attributes/field';
import { fieldExtensions as costOfGoodsSoldFieldExtensions } from './cost_of_goods_sold/field';
import { fieldExtensions as dateOnSaleFromFieldExtensions } from './date_on_sale_from/field';
import { fieldExtensions as dateOnSaleToFieldExtensions } from './date_on_sale_to/field';
import { fieldExtensions as descriptionFieldExtensions } from './description/field';
import { fieldExtensions as downloadableFieldExtensions } from './downloadable/field';
import { fieldExtensions as downloadableFilesFieldExtensions } from './downloadable_files/field';
import { fieldExtensions as downloadExpiryFieldExtensions } from './download_expiry/field';
import { fieldExtensions as downloadLimitFieldExtensions } from './download_limit/field';
import { fieldExtensions as globalUniqueIdFieldExtensions } from './global_unique_id/field';
import { fieldExtensions as heightFieldExtensions } from './height/field';
import { fieldExtensions as imagesFieldExtensions } from './images/field';
import { fieldExtensions as lengthFieldExtensions } from './length/field';
import { fieldExtensions as lowStockAmountFieldExtensions } from './low_stock_amount/field';
import { fieldExtensions as manageStockFieldExtensions } from './manage_stock/field';
import { fieldExtensions as regularPriceFieldExtensions } from './regular_price/field';
import { fieldExtensions as salePriceFieldExtensions } from './sale_price/field';
import { fieldExtensions as scheduleSaleFieldExtensions } from './schedule_sale/field';
import { fieldExtensions as shippingClassFieldExtensions } from './shipping_class/field';
import { fieldExtensions as skuFieldExtensions } from './sku/field';
import { fieldExtensions as stockFieldExtensions } from './stock/field';
import { fieldExtensions as stockQuantityFieldExtensions } from './stock_quantity/field';
import { fieldExtensions as taxClassFieldExtensions } from './tax_class/field';
import { fieldExtensions as taxStatusFieldExtensions } from './tax_status/field';
import { fieldExtensions as variationActiveFieldExtensions } from './variation_active/field';
import { fieldExtensions as virtualFieldExtensions } from './virtual/field';
import { fieldExtensions as weightFieldExtensions } from './weight/field';
import { fieldExtensions as widthFieldExtensions } from './width/field';

type VariationEditField = Field< ProductEntityRecord >;

// TODO: Move all product field definitions into a common fields folder once
// the product list/edit pages and variation edit drawer standardize their
// UI/UX. The current page-specific designs require separate field
// implementations even when the underlying product data is the same.

function withVirtualGuard( field: VariationEditField ): VariationEditField {
	const existing = field.isVisible;
	return {
		...field,
		isVisible: ( item: ProductEntityRecord ) =>
			! item.virtual && ( existing === undefined || existing( item ) ),
	};
}

const SHARED_FIELDS: Array< {
	id: string;
	extensions: Partial< VariationEditField >;
} > = [
	{ id: 'description', extensions: descriptionFieldExtensions },
	{ id: 'sku', extensions: skuFieldExtensions },
	{ id: 'global_unique_id', extensions: globalUniqueIdFieldExtensions },
	{ id: 'regular_price', extensions: regularPriceFieldExtensions },
	{ id: 'sale_price', extensions: salePriceFieldExtensions },
	{ id: 'schedule_sale', extensions: scheduleSaleFieldExtensions },
	{ id: 'date_on_sale_from', extensions: dateOnSaleFromFieldExtensions },
	{ id: 'date_on_sale_to', extensions: dateOnSaleToFieldExtensions },
	{ id: 'cost_of_goods_sold', extensions: costOfGoodsSoldFieldExtensions },
	{ id: 'images', extensions: imagesFieldExtensions },
	{ id: 'manage_stock', extensions: manageStockFieldExtensions },
	{ id: 'stock', extensions: stockFieldExtensions },
	{ id: 'stock_quantity', extensions: stockQuantityFieldExtensions },
	{ id: 'tax_status', extensions: taxStatusFieldExtensions },
	{ id: 'variation_active', extensions: variationActiveFieldExtensions },
];

const SHIPPING_FIELDS: Array< {
	id: string;
	extensions: Partial< VariationEditField >;
} > = [
	{ id: 'shipping_class', extensions: shippingClassFieldExtensions },
	{ id: 'weight', extensions: weightFieldExtensions },
	{ id: 'length', extensions: lengthFieldExtensions },
	{ id: 'width', extensions: widthFieldExtensions },
	{ id: 'height', extensions: heightFieldExtensions },
];

const VARIATION_ONLY_FIELDS: Array< {
	id: string;
	extensions: Partial< VariationEditField >;
} > = [
	{ id: 'downloadable', extensions: downloadableFieldExtensions },
	{ id: 'tax_class', extensions: taxClassFieldExtensions },
	{ id: 'virtual', extensions: virtualFieldExtensions },
	{ id: 'allow_backorders', extensions: allowBackordersFieldExtensions },
	{ id: 'low_stock_amount', extensions: lowStockAmountFieldExtensions },
	{ id: 'downloadable_files', extensions: downloadableFilesFieldExtensions },
	{ id: 'download_limit', extensions: downloadLimitFieldExtensions },
	{ id: 'download_expiry', extensions: downloadExpiryFieldExtensions },
	{ id: 'attributes', extensions: attributesFieldExtensions },
];

const sharedFields: VariationEditField[] = SHARED_FIELDS.map(
	( { id, extensions } ) => ( { id, ...extensions } ) as VariationEditField
);

const shippingFields: VariationEditField[] = SHIPPING_FIELDS.map(
	( { id, extensions } ) =>
		withVirtualGuard( { id, ...extensions } as VariationEditField )
);

const variationOnlyFields: VariationEditField[] = VARIATION_ONLY_FIELDS.map(
	( { id, extensions } ) => ( { id, ...extensions } ) as VariationEditField
);

// The full field list used by the variation edit drawer.
export const variationEditFields: VariationEditField[] = [
	...sharedFields,
	...shippingFields,
	...variationOnlyFields,
];

export type VariationEditFieldId =
	| ( typeof SHARED_FIELDS )[ number ][ 'id' ]
	| ( typeof SHIPPING_FIELDS )[ number ][ 'id' ]
	| ( typeof VARIATION_ONLY_FIELDS )[ number ][ 'id' ];
