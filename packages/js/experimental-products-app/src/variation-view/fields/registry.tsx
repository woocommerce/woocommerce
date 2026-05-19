/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { fieldExtensions as costOfGoodsSoldFieldExtensions } from './cost_of_goods_sold/field';
import { fieldExtensions as dateOnSaleFromFieldExtensions } from './date_on_sale_from/field';
import { fieldExtensions as dateOnSaleToFieldExtensions } from './date_on_sale_to/field';
import { fieldExtensions as descriptionFieldExtensions } from './description/field';
import { fieldExtensions as downloadableFieldExtensions } from './downloadable/field';
import { fieldExtensions as heightFieldExtensions } from './height/field';
import { fieldExtensions as imagesFieldExtensions } from './images/field';
import { fieldExtensions as lengthFieldExtensions } from './length/field';
import { fieldExtensions as manageStockFieldExtensions } from './manage_stock/field';
import { fieldExtensions as productStatusFieldExtensions } from './product_status/field';
import { fieldExtensions as regularPriceFieldExtensions } from './regular_price/field';
import { fieldExtensions as salePriceFieldExtensions } from './sale_price/field';
import { fieldExtensions as scheduleSaleFieldExtensions } from './schedule_sale/field';
import { fieldExtensions as shippingClassFieldExtensions } from './shipping_class/field';
import { fieldExtensions as skuFieldExtensions } from './sku/field';
import { fieldExtensions as stockFieldExtensions } from './stock/field';
import { fieldExtensions as stockQuantityFieldExtensions } from './stock_quantity/field';
import { fieldExtensions as taxStatusFieldExtensions } from './tax_status/field';
import type { ProductEntityRecord } from './types';
import { fieldExtensions as weightFieldExtensions } from './weight/field';
import { fieldExtensions as widthFieldExtensions } from './width/field';

type VariationEditField = Field< ProductEntityRecord >;
type VariationEditFieldExtensions = Partial< VariationEditField >;

export const VARIATION_EDIT_FIELD_IDS = [
	'product_status',
	'description',
	'sku',
	'regular_price',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'cost_of_goods_sold',
	'images',
	'manage_stock',
	'stock',
	'stock_quantity',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'tax_status',
	'downloadable',
] as const;

export type VariationEditFieldId =
	( typeof VARIATION_EDIT_FIELD_IDS )[ number ];

const VARIATION_FIELD_EXTENSIONS: Record<
	VariationEditFieldId,
	VariationEditFieldExtensions
> = {
	product_status: productStatusFieldExtensions,
	description: descriptionFieldExtensions,
	sku: skuFieldExtensions,
	regular_price: regularPriceFieldExtensions,
	sale_price: salePriceFieldExtensions,
	schedule_sale: scheduleSaleFieldExtensions,
	date_on_sale_from: dateOnSaleFromFieldExtensions,
	date_on_sale_to: dateOnSaleToFieldExtensions,
	cost_of_goods_sold: costOfGoodsSoldFieldExtensions,
	images: imagesFieldExtensions,
	manage_stock: manageStockFieldExtensions,
	stock: stockFieldExtensions,
	stock_quantity: stockQuantityFieldExtensions,
	weight: weightFieldExtensions,
	length: lengthFieldExtensions,
	width: widthFieldExtensions,
	height: heightFieldExtensions,
	shipping_class: shippingClassFieldExtensions,
	tax_status: taxStatusFieldExtensions,
	downloadable: downloadableFieldExtensions,
};

export function createVariationEditField( id: VariationEditFieldId ): VariationEditField {
	return {
		id,
		...VARIATION_FIELD_EXTENSIONS[ id ],
	};
}

export function createVariationEditFields(
	fieldIds: readonly VariationEditFieldId[]
): VariationEditField[] {
	return fieldIds.map( createVariationEditField );
}

export const variationEditFields = createVariationEditFields(
	VARIATION_EDIT_FIELD_IDS
);
