/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { fieldExtensions as brandsFieldExtensions } from './brands/field';
import { fieldExtensions as buttonTextFieldExtensions } from './button_text/field';
import { fieldExtensions as catalogVisibilityFieldExtensions } from './catalog_visibility/field';
import { fieldExtensions as categoriesFieldExtensions } from './categories/field';
import { fieldExtensions as costOfGoodsSoldFieldExtensions } from './cost_of_goods_sold/field';
import { fieldExtensions as crossSellIdsFieldExtensions } from './cross_sell_ids/field';
import { fieldExtensions as dateFieldExtensions } from './date/field';
import { fieldExtensions as dateOnSaleFromFieldExtensions } from './date_on_sale_from/field';
import { fieldExtensions as dateOnSaleToFieldExtensions } from './date_on_sale_to/field';
import { fieldExtensions as descriptionFieldExtensions } from './description/field';
import { fieldExtensions as globalUniqueIdFieldExtensions } from './global_unique_id/field';
import { fieldExtensions as groupedProductsFieldExtensions } from './grouped_products/field';
import { fieldExtensions as downloadableFieldExtensions } from './downloadable/field';
import { fieldExtensions as downloadableCountFieldExtensions } from './downloadable_count/field';
import { fieldExtensions as externalUrlFieldExtensions } from './external_url/field';
import { fieldExtensions as featuredFieldExtensions } from './featured/field';
import { fieldExtensions as heightFieldExtensions } from './height/field';
import { fieldExtensions as imagesFieldExtensions } from './images/field';
import { fieldExtensions as imagesCountFieldExtensions } from './images_count/field';
import { fieldExtensions as inventorySummaryFieldExtensions } from './inventory_summary/field';
import { fieldExtensions as lengthFieldExtensions } from './length/field';
import { fieldExtensions as linkedProductsCountFieldExtensions } from './linked_products_count/field';
import { fieldExtensions as manageStockFieldExtensions } from './manage_stock/field';
import { fieldExtensions as nameFieldExtensions } from './name/field';
import { fieldExtensions as onSaleFieldExtensions } from './on_sale/field';
import { fieldExtensions as organizationSummaryFieldExtensions } from './organization_summary/field';
import { fieldExtensions as priceFieldExtensions } from './price/field';
import { fieldExtensions as priceSummaryFieldExtensions } from './price_summary/field';
import { fieldExtensions as productStatusFieldExtensions } from './product_status/field';
import { fieldExtensions as regularPriceFieldExtensions } from './regular_price/field';
import { fieldExtensions as salePriceFieldExtensions } from './sale_price/field';
import { fieldExtensions as scheduleSaleFieldExtensions } from './schedule_sale/field';
import { fieldExtensions as shippingClassFieldExtensions } from './shipping_class/field';
import { fieldExtensions as shippingSummaryFieldExtensions } from './shipping_summary/field';
import { fieldExtensions as shortDescriptionFieldExtensions } from './short_description/field';
import { fieldExtensions as skuFieldExtensions } from './sku/field';
import { fieldExtensions as stockFieldExtensions } from './stock/field';
import { fieldExtensions as stockQuantityFieldExtensions } from './stock_quantity/field';
import { fieldExtensions as tagsFieldExtensions } from './tags/field';
import { fieldExtensions as taxStatusFieldExtensions } from './tax_status/field';
import type { ProductEntityRecord } from './types';
import { fieldExtensions as typeFieldExtensions } from './type/field';
import { fieldExtensions as upsellIdsFieldExtensions } from './upsell_ids/field';
import { fieldExtensions as variationActiveFieldExtensions } from './variation_active/field';
import { fieldExtensions as visibilitySummaryFieldExtensions } from './visibility_summary/field';
import { fieldExtensions as weightFieldExtensions } from './weight/field';
import { fieldExtensions as widthFieldExtensions } from './width/field';

type ProductField = Field< ProductEntityRecord >;
type ProductFieldExtensions = Partial< ProductField >;

export const PRODUCT_FIELD_IDS = [
	'name',
	'short_description',
	'description',
	'images',
	'images_count',
	'product_status',
	'variation_active',
	'sku',
	'price',
	'regular_price',
	'on_sale',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'cost_of_goods_sold',
	'price_summary',
	'stock',
	'stock_quantity',
	'manage_stock',
	'inventory_summary',
	'categories',
	'tags',
	'brands',
	'date',
	'global_unique_id',
	'grouped_products',
	'organization_summary',
	'type',
	'featured',
	'catalog_visibility',
	'visibility_summary',
	'downloadable',
	'downloadable_count',
	'external_url',
	'button_text',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'shipping_summary',
	'tax_status',
	'upsell_ids',
	'cross_sell_ids',
	'linked_products_count',
] as const;

export type ProductFieldId = ( typeof PRODUCT_FIELD_IDS )[ number ];

const PRODUCT_FIELD_EXTENSIONS: Record<
	ProductFieldId,
	ProductFieldExtensions
> = {
	name: nameFieldExtensions,
	short_description: shortDescriptionFieldExtensions,
	description: descriptionFieldExtensions,
	images: imagesFieldExtensions,
	images_count: imagesCountFieldExtensions,
	product_status: productStatusFieldExtensions,
	variation_active: variationActiveFieldExtensions,
	sku: skuFieldExtensions,
	price: priceFieldExtensions as ProductFieldExtensions,
	regular_price: regularPriceFieldExtensions,
	sale_price: salePriceFieldExtensions,
	schedule_sale: scheduleSaleFieldExtensions,
	date_on_sale_from: dateOnSaleFromFieldExtensions,
	date_on_sale_to: dateOnSaleToFieldExtensions,
	cost_of_goods_sold: costOfGoodsSoldFieldExtensions,
	on_sale: onSaleFieldExtensions,
	price_summary: priceSummaryFieldExtensions,
	stock: stockFieldExtensions,
	stock_quantity: stockQuantityFieldExtensions,
	manage_stock: manageStockFieldExtensions,
	inventory_summary: inventorySummaryFieldExtensions,
	categories: categoriesFieldExtensions,
	tags: tagsFieldExtensions,
	brands: brandsFieldExtensions,
	date: dateFieldExtensions,
	global_unique_id: globalUniqueIdFieldExtensions,
	grouped_products: groupedProductsFieldExtensions,
	organization_summary: organizationSummaryFieldExtensions,
	type: typeFieldExtensions,
	featured: featuredFieldExtensions,
	catalog_visibility: catalogVisibilityFieldExtensions,
	visibility_summary: visibilitySummaryFieldExtensions,
	downloadable: downloadableFieldExtensions,
	downloadable_count: downloadableCountFieldExtensions,
	external_url: externalUrlFieldExtensions,
	button_text: buttonTextFieldExtensions,
	weight: weightFieldExtensions,
	length: lengthFieldExtensions,
	width: widthFieldExtensions,
	height: heightFieldExtensions,
	shipping_class: shippingClassFieldExtensions,
	shipping_summary: shippingSummaryFieldExtensions,
	tax_status: taxStatusFieldExtensions,
	upsell_ids: upsellIdsFieldExtensions,
	cross_sell_ids: crossSellIdsFieldExtensions,
	linked_products_count: linkedProductsCountFieldExtensions,
};

export function createProductField( id: ProductFieldId ): ProductField {
	return {
		id,
		...PRODUCT_FIELD_EXTENSIONS[ id ],
	};
}

export function createProductFields(
	fieldIds: readonly ProductFieldId[]
): ProductField[] {
	return fieldIds.map( createProductField );
}
