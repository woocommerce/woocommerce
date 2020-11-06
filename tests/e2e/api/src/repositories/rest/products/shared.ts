import { ModelTransformation, ModelTransformer } from '../../../framework/model-transformer';
import { KeyChangeTransformation } from '../../../framework/transformations/key-change-transformation';
import { AbstractProduct } from '../../../models/products/abstract-product';
import { AddPropertyTransformation } from '../../../framework/transformations/add-property-transformation';
import { IgnorePropertyTransformation } from '../../../framework/transformations/ignore-property-transformation';
import {
	PropertyType,
	PropertyTypeTransformation,
} from '../../../framework/transformations/property-type-transformation';

/**
 * Creates a transformer for the shared properties of all products.
 *
 * @param {string} type The product type.
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transform.
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
		new PropertyTypeTransformation(
			{
				created: PropertyType.Date,
				modified: PropertyType.Date,
				isPurchasable: PropertyType.Boolean,
				isFeatured: PropertyType.Boolean,
				isVirtual: PropertyType.Boolean,
				onePerOrder: PropertyType.Boolean,
				onSale: PropertyType.Boolean,
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
			},
		),
	);

	return new ModelTransformer( transformations );
}
