import {
	AddPropertyTransformation,
	CustomTransformation,
	IgnorePropertyTransformation,
	KeyChangeTransformation,
	ModelTransformation,
	ModelTransformer,
	ModelTransformerTransformation,
	PropertyType,
	PropertyTypeTransformation,
	TransformationOrder,
} from '../../../framework';
import {
	AbstractProduct,
	AbstractProductData,
	IProductCrossSells,
	IProductDelivery,
	IProductExternal,
	IProductGrouped,
	IProductInventory,
	IProductPrice,
	IProductSalesTax,
	IProductShipping,
	IProductUpSells,
	MetaData,
	ProductAttribute,
	ProductDownload,
	ProductImage,
	ProductTerm,
	VariableProduct,
} from '../../../models';
import { createMetaDataTransformer } from '../shared';

/**
 * Creates a transformer for the product term object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductTermTransformer(): ModelTransformer< ProductTerm > {
	return new ModelTransformer(
		[
			new PropertyTypeTransformation( { id: PropertyType.Integer } ),
		],
	);
}

/**
 * Creates a transformer for the product attribute object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductAttributeTransformer(): ModelTransformer< ProductAttribute > {
	return new ModelTransformer(
		[
			new PropertyTypeTransformation(
				{
					id: PropertyType.Integer,
					sortOrder: PropertyType.Integer,
					isVisibleOnProductPage: PropertyType.Boolean,
					isForVariations: PropertyType.Boolean,
				},
			),
			new KeyChangeTransformation< ProductAttribute >(
				{
					sortOrder: 'position',
					isVisibleOnProductPage: 'visible',
					isForVariations: 'variation',
				},
			),
		],
	);
}

/**
 * Creates a transformer for the product image object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductImageTransformer(): ModelTransformer< ProductImage > {
	return new ModelTransformer(
		[
			new IgnorePropertyTransformation( [ 'date_created', 'date_modified' ] ),
			new PropertyTypeTransformation(
				{
					id: PropertyType.Integer,
					created: PropertyType.Date,
					modified: PropertyType.Date,
				},
			),
			new KeyChangeTransformation< ProductImage >(
				{
					created: 'date_created_gmt',
					modified: 'date_modified_gmt',
					url: 'src',
					altText: 'altText',
				},
			),
		],
	);
}

/**
 * Creates a transformer for the product download object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductDownloadTransformer(): ModelTransformer< ProductDownload > {
	return new ModelTransformer(
		[
			new KeyChangeTransformation< ProductDownload >( { url: 'file' } ),
		],
	);
}

/**
 * Creates a transformer for the base product property data.
 *
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transformer.
 */
export function createProductDataTransformer< T extends AbstractProductData >(
	transformations?: ModelTransformation[],
): ModelTransformer< T > {
	if ( ! transformations ) {
		transformations = [];
	}

	transformations.push(
		new IgnorePropertyTransformation(
			[
				'date_created',
				'date_modified',
			],
		),
		new ModelTransformerTransformation( 'images', ProductImage, createProductImageTransformer() ),
		new ModelTransformerTransformation( 'metaData', MetaData, createMetaDataTransformer() ),
		new PropertyTypeTransformation(
			{
				created: PropertyType.Date,
				modified: PropertyType.Date,
				isPurchasable: PropertyType.Boolean,
				parentId: PropertyType.Integer,
				menuOrder: PropertyType.Integer,
				permalink: PropertyType.String,
			},
		),
		new KeyChangeTransformation< AbstractProductData >(
			{
				created: 'date_created_gmt',
				modified: 'date_modified_gmt',
				postStatus: 'status',
				isPurchasable: 'purchasable',
				metaData: 'meta_data',
				parentId: 'parent_id',
				menuOrder: 'menu_order',
				links: '_links',
			},
		),
	);

	return new ModelTransformer( transformations );
}

/**
 * Creates a transformer for the shared properties of all products.
 *
 * @param {string} type The product type.
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transformer.
 */
export function createProductTransformer< T extends AbstractProduct >(
	type: string,
	transformations?: ModelTransformation[],
): ModelTransformer< T > {
	if ( ! transformations ) {
		transformations = [];
	}

	transformations.push(
		new AddPropertyTransformation( {}, { type } ),
		new ModelTransformerTransformation( 'categories', ProductTerm, createProductTermTransformer() ),
		new ModelTransformerTransformation( 'tags', ProductTerm, createProductTermTransformer() ),
		new ModelTransformerTransformation( 'attributes', ProductAttribute, createProductAttributeTransformer() ),
		new PropertyTypeTransformation(
			{
				isFeatured: PropertyType.Boolean,
				allowReviews: PropertyType.Boolean,
				averageRating: PropertyType.Integer,
				numRatings: PropertyType.Integer,
				totalSales: PropertyType.Integer,
				relatedIds: PropertyType.Integer,
			},
		),
		new KeyChangeTransformation< AbstractProduct >(
			{
				shortDescription: 'short_description',
				isFeatured: 'featured',
				catalogVisibility: 'catalog_visibility',
				allowReviews: 'reviews_allowed',
				averageRating: 'average_rating',
				numRatings: 'rating_count',
				totalSales: 'total_sales',
				relatedIds: 'related_ids',
			},
		),
	);

	return createProductDataTransformer< T >( transformations );
}

/**
 * Create a transformer for the product price properties.
 */
export function createProductPriceTransformation(): ModelTransformation[] {
	const transformations = [
		new IgnorePropertyTransformation(
			[
				'date_on_sale_from',
				'date_on_sale_to',
			],
		),
		new PropertyTypeTransformation(
			{
				onSale: PropertyType.Boolean,
				saleStart: PropertyType.Date,
				saleEnd: PropertyType.Date,
				priceHtml: PropertyType.String,
			},
		),
		new KeyChangeTransformation< IProductPrice >(
			{
				regularPrice: 'regular_price',
				onSale: 'on_sale',
				salePrice: 'sale_price',
				saleStart: 'date_on_sale_from_gmt',
				saleEnd: 'date_on_sale_to_gmt',
				priceHtml: 'price_html',
			},
		),
	];

	return transformations;
}

/**
 * Create a transformer for the product cross sells property.
 */
export function createProductCrossSellsTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				crossSellIds: PropertyType.Integer,
			},
		),
		new KeyChangeTransformation< IProductCrossSells >(
			{
				crossSellIds: 'cross_sell_ids',
			},
		),
	];

	return transformations;
}

/**
 * Create a transformer for the product upsells property.
 */
export function createProductUpSellsTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				upSellIds: PropertyType.Integer,
			},
		),
		new KeyChangeTransformation< IProductUpSells >(
			{
				upSellIds: 'upsell_ids',
			},
		),
	];

	return transformations;
}

/**
 * Transformer for the grouped products property.
 */
export function createProductGroupedTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				groupedProducts: PropertyType.Integer,
			},
		),
		new KeyChangeTransformation< IProductGrouped >(
			{
				groupedProducts: 'grouped_products',
			},
		),
	];

	return transformations;
}

/**
 * Create a transformer for product delivery properties.
 */
export function createProductDeliveryTransformation(): ModelTransformation[] {
	const transformations = [
		new ModelTransformerTransformation( 'downloads', ProductDownload, createProductDownloadTransformer() ),
		new PropertyTypeTransformation(
			{
				isVirtual: PropertyType.Boolean,
				isDownloadable: PropertyType.Boolean,
				downloadLimit: PropertyType.Integer,
				daysToDownload: PropertyType.Integer,
				purchaseNote: PropertyType.String,
			},
		),
		new KeyChangeTransformation< IProductDelivery >(
			{
				isVirtual: 'virtual',
				isDownloadable: 'downloadable',
				downloadLimit: 'download_limit',
				daysToDownload: 'download_expiry',
				purchaseNote: 'purchase_note',
			},
		),
	];

	return transformations;
}

/**
 * Create a transformer for product inventory properties.
 */
export function createProductInventoryTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				trackInventory: PropertyType.Boolean,
				remainingStock: PropertyType.Integer,
				canBackorder: PropertyType.Boolean,
				isOnBackorder: PropertyType.Boolean,
				onePerOrder: PropertyType.Boolean,
				stockStatus: PropertyType.String,
				backOrderStatus: PropertyType.String,
			},
		),
		new KeyChangeTransformation< IProductInventory >(
			{
				trackInventory: 'manage_stock',
				remainingStock: 'stock_quantity',
				stockStatus: 'stock_status',
				onePerOrder: 'sold_individually',
				backorderStatus: 'backorders',
				canBackorder: 'backorders_allowed',
				isOnBackorder: 'backordered',
			},
		),
	];

	return transformations;
}

/**
 * Create a transformer for product sales tax properties.
 */
export function createProductSalesTaxTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				taxClass: PropertyType.String,
				taxStatus: PropertyType.String,
			},
		),
		new KeyChangeTransformation< IProductSalesTax >(
			{
				taxStatus: 'tax_status',
				taxClass: 'tax_class',
			},
		),
	];

	return transformations;
}

/**
 * Create a transformer for product shipping properties.
 */
export function createProductShippingTransformation(): ModelTransformation[] {
	const transformations = [
		new CustomTransformation(
			TransformationOrder.Normal,
			( properties: any ) => {
				if ( properties.hasOwnProperty( 'dimensions' ) ) {
					properties.length = properties.dimensions.length;
					properties.width = properties.dimensions.width;
					properties.height = properties.dimensions.height;
					delete properties.dimensions;
				}

				return properties;
			},
			( properties: any ) => {
				if ( properties.hasOwnProperty( 'length ' ) ||
					properties.hasOwnProperty( 'width' ) ||
					properties.hasOwnProperty( 'height' ) ) {
					properties.dimensions = {
						length: properties.length,
						width: properties.width,
						height: properties.height,
					};
					delete properties.length;
					delete properties.width;
					delete properties.height;
				}

				return properties;
			},
		),
		new PropertyTypeTransformation(
			{
				requiresShipping: PropertyType.Boolean,
				isShippingTaxable: PropertyType.Boolean,
				shippingClass: PropertyType.String,
				shippingClassId: PropertyType.Integer,
				weight: PropertyType.String,
			},
		),
		new KeyChangeTransformation< IProductShipping >(
			{
				requiresShipping: 'shipping_required',
				isShippingTaxable: 'shipping_taxable',
				shippingClass: 'shipping_class',
				shippingClassId: 'shipping_class_id',
			},
		),
	];

	return transformations;
}

/**
 * Variable product specific properties transformations
 */
export function createProductVariableTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				id: PropertyType.Integer,
				name: PropertyType.String,
				option: PropertyType.String,
				variations: PropertyType.Integer,
			},
		),
		new KeyChangeTransformation< VariableProduct >(
			{
				defaultAttributes: 'default_attributes',
			},
		),
	];

	return transformations;
}

/**
 * Transformer for the properties unique to the external product type.
 */
export function createProductExternalTransformation(): ModelTransformation[] {
	const transformations = [
		new PropertyTypeTransformation(
			{
				buttonText: PropertyType.String,
				externalUrl: PropertyType.String,
			},
		),
		new KeyChangeTransformation< IProductExternal >(
			{
				buttonText: 'button_text',
				externalUrl: 'external_url',
			},
		),
	];

	return transformations;
}
