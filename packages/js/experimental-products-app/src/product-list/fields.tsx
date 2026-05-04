/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { fieldExtensions as buttonTextFieldExtensions } from '../fields/button_text/field';
import { fieldExtensions as catalogVisibilityFieldExtensions } from '../fields/catalog_visibility/field';
import { fieldExtensions as categoriesFieldExtensions } from '../fields/categories/field';
import { fieldExtensions as crossSellIdsFieldExtensions } from '../fields/cross_sell_ids/field';
import { fieldExtensions as dateOnSaleFromFieldExtensions } from '../fields/date_on_sale_from/field';
import { fieldExtensions as dateOnSaleToFieldExtensions } from '../fields/date_on_sale_to/field';
import { fieldExtensions as descriptionFieldExtensions } from '../fields/description/field';
import { fieldExtensions as downloadableFieldExtensions } from '../fields/downloadable/field';
import { fieldExtensions as downloadableCountFieldExtensions } from '../fields/downloadable_count/field';
import { fieldExtensions as externalUrlFieldExtensions } from '../fields/external_url/field';
import { fieldExtensions as featuredFieldExtensions } from '../fields/featured/field';
import { fieldExtensions as heightFieldExtensions } from '../fields/height/field';
import { fieldExtensions as imagesFieldExtensions } from '../fields/images/field';
import { fieldExtensions as imagesCountFieldExtensions } from '../fields/images_count/field';
import { fieldExtensions as inventorySummaryFieldExtensions } from '../fields/inventory_summary/field';
import { fieldExtensions as lengthFieldExtensions } from '../fields/length/field';
import { fieldExtensions as linkedProductsCountFieldExtensions } from '../fields/linked_products_count/field';
import { fieldExtensions as manageStockFieldExtensions } from '../fields/manage_stock/field';
import { fieldExtensions as nameFieldExtensions } from '../fields/name/field';
import { fieldExtensions as onSaleFieldExtensions } from '../fields/on_sale/field';
import { fieldExtensions as organizationSummaryFieldExtensions } from '../fields/organization_summary/field';
import { fieldExtensions as priceFieldExtensions } from '../fields/price/field';
import { fieldExtensions as priceSummaryFieldExtensions } from '../fields/price_summary/field';
import { fieldExtensions as productStatusFieldExtensions } from '../fields/product_status/field';
import { fieldExtensions as regularPriceFieldExtensions } from '../fields/regular_price/field';
import { fieldExtensions as salePriceFieldExtensions } from '../fields/sale_price/field';
import { fieldExtensions as scheduleSaleFieldExtensions } from '../fields/schedule_sale/field';
import { fieldExtensions as shippingClassFieldExtensions } from '../fields/shipping_class/field';
import { fieldExtensions as shippingSummaryFieldExtensions } from '../fields/shipping_summary/field';
import { fieldExtensions as shortDescriptionFieldExtensions } from '../fields/short_description/field';
import { fieldExtensions as skuFieldExtensions } from '../fields/sku/field';
import { fieldExtensions as stockFieldExtensions } from '../fields/stock/field';
import { fieldExtensions as stockQuantityFieldExtensions } from '../fields/stock_quantity/field';
import { fieldExtensions as tagsFieldExtensions } from '../fields/tags/field';
import { fieldExtensions as taxStatusFieldExtensions } from '../fields/tax_status/field';
import type { ProductEntityRecord } from '../fields/types';
import { fieldExtensions as typeFieldExtensions } from '../fields/type/field';
import { fieldExtensions as upsellIdsFieldExtensions } from '../fields/upsell_ids/field';
import { fieldExtensions as visibilitySummaryFieldExtensions } from '../fields/visibility_summary/field';
import { fieldExtensions as weightFieldExtensions } from '../fields/weight/field';
import { fieldExtensions as widthFieldExtensions } from '../fields/width/field';

type ProductField = Field< ProductEntityRecord >;
type ProductFieldExtensions = Partial< ProductField >;

const PRODUCT_LIST_FIELD_IDS = [
	'name',
	'short_description',
	'description',
	'images',
	'images_count',
	'product_status',
	'sku',
	'price',
	'regular_price',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'on_sale',
	'price_summary',
	'stock',
	'stock_quantity',
	'manage_stock',
	'inventory_summary',
	'categories',
	'tags',
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

const PRODUCT_LIST_FIELD_EXTENSIONS: Record< string, ProductFieldExtensions > =
	{
		name: nameFieldExtensions,
		short_description: shortDescriptionFieldExtensions,
		description: descriptionFieldExtensions,
		images: imagesFieldExtensions,
		images_count: imagesCountFieldExtensions,
		product_status: productStatusFieldExtensions,
		sku: skuFieldExtensions,
		price: priceFieldExtensions as ProductFieldExtensions,
		regular_price: regularPriceFieldExtensions,
		sale_price: salePriceFieldExtensions,
		schedule_sale: scheduleSaleFieldExtensions,
		date_on_sale_from: dateOnSaleFromFieldExtensions,
		date_on_sale_to: dateOnSaleToFieldExtensions,
		on_sale: onSaleFieldExtensions,
		price_summary: priceSummaryFieldExtensions,
		stock: stockFieldExtensions,
		stock_quantity: stockQuantityFieldExtensions,
		manage_stock: manageStockFieldExtensions,
		inventory_summary: inventorySummaryFieldExtensions,
		categories: categoriesFieldExtensions,
		tags: tagsFieldExtensions,
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

export const productFields: ProductField[] = PRODUCT_LIST_FIELD_IDS.map(
	( id ) => ( {
		id,
		...PRODUCT_LIST_FIELD_EXTENSIONS[ id ],
	} )
);
