import {
	ModelTransformation,
	ModelTransformer,
	TransformationOrder,
	KeyChangeTransformation,
	AddPropertyTransformation,
	IgnorePropertyTransformation,
	PropertyType,
	PropertyTypeTransformation,
	CustomTransformation,
	ModelTransformerTransformation,
} from '../../../framework';
import {
	AbstractProduct,
	ProductAttribute,
	ProductDownload,
	ProductImage,
	ProductTerm,
	MetaData,
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
		new IgnorePropertyTransformation(
			[
				'date_created',
				'date_modified',
				'date_on_sale_from',
				'date_on_sale_to',
			],
		),
		new ModelTransformerTransformation( 'categories', ProductTerm, createProductTermTransformer() ),
		new ModelTransformerTransformation( 'tags', ProductTerm, createProductTermTransformer() ),
		new ModelTransformerTransformation( 'attributes', ProductAttribute, createProductAttributeTransformer() ),
		new ModelTransformerTransformation( 'images', ProductImage, createProductImageTransformer() ),
		new ModelTransformerTransformation( 'downloads', ProductDownload, createProductDownloadTransformer() ),
		new ModelTransformerTransformation( 'metaData', MetaData, createMetaDataTransformer() ),
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
				created: PropertyType.Date,
				modified: PropertyType.Date,
				isPurchasable: PropertyType.Boolean,
				isFeatured: PropertyType.Boolean,
				isVirtual: PropertyType.Boolean,
				onePerOrder: PropertyType.Boolean,
				onSale: PropertyType.Boolean,
				saleStart: PropertyType.Date,
				saleEnd: PropertyType.Date,
				isDownloadable: PropertyType.Boolean,
				downloadLimit: PropertyType.Integer,
				daysToDownload: PropertyType.Integer,
				requiresShipping: PropertyType.Boolean,
				isShippingTaxable: PropertyType.Boolean,
				trackInventory: PropertyType.Boolean,
				remainingStock: PropertyType.Integer,
				canBackorder: PropertyType.Boolean,
				isOnBackorder: PropertyType.Boolean,
				allowReviews: PropertyType.Boolean,
				averageRating: PropertyType.Integer,
				numRatings: PropertyType.Integer,
			},
		),
		new KeyChangeTransformation< AbstractProduct >(
			{
				created: 'date_created_gmt',
				modified: 'date_modified_gmt',
				postStatus: 'status',
				shortDescription: 'short_description',
				isPurchasable: 'purchasable',
				isFeatured: 'featured',
				isVirtual: 'virtual',
				catalogVisibility: 'catalog_visibility',
				regularPrice: 'regular_price',
				onePerOrder: 'sold_individually',
				taxStatus: 'tax_status',
				taxClass: 'tax_class',
				onSale: 'on_sale',
				salePrice: 'sale_price',
				saleStart: 'date_on_sale_from_gmt',
				saleEnd: 'date_on_sale_to_gmt',
				isDownloadable: 'downloadable',
				downloadLimit: 'download_limit',
				daysToDownload: 'download_expiry',
				requiresShipping: 'shipping_required',
				isShippingTaxable: 'shipping_taxable',
				shippingClass: 'shipping_class',
				trackInventory: 'manage_stock',
				remainingStock: 'stock_quantity',
				stockStatus: 'stock_status',
				backorderStatus: 'backorders',
				canBackorder: 'backorders_allowed',
				isOnBackorder: 'backordered',
				allowReviews: 'reviews_allowed',
				averageRating: 'average_rating',
				numRatings: 'rating_count',
				metaData: 'meta_data',
			},
		),
	);

	return new ModelTransformer( transformations );
}
