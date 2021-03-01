import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	ProductVariation,
	ModelID,
	CreatesProductVariations,
	DeletesProductVariations,
	ListsProductVariations,
	ReadsProductVariations,
	ProductVariationRepositoryParams,
	UpdatesProductVariations,
} from '../../../models';
import {
	createProductDataTransformer,
	createProductDeliveryTransformation,
	createProductInventoryTransformation,
	createProductSalesTaxTransformation,
	createProductShippingTransformation,
} from './shared';
import {
	restCreateChild,
	restDeleteChild,
	restListChild,
	restReadChild,
	restUpdateChild,
} from '../shared';

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsProductVariations|
 * 	CreatesProductVariations|
 * 	ReadsProductVariations|
 * 	UpdatesProductVariations|
 * 	DeletesProductVariations
 * } The created repository.
 */
export function productVariationRESTRepository( httpClient: HTTPClient ): ListsProductVariations
	& CreatesProductVariations
	& ReadsProductVariations
	& UpdatesProductVariations
	& DeletesProductVariations {
	const buildURL = ( id: ModelID ) => '/wc/v3/products/' + id;
	const buildChildURL = ( parent: ModelID, id: ModelID ) => '/wc/v3/products/' + parent + '/variations/' + id;

	const delivery = createProductDeliveryTransformation();
	const inventory = createProductInventoryTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const shipping = createProductShippingTransformation();
	const transformations = [
		...delivery,
		...inventory,
		...salesTax,
		...shipping,
	];

	const transformer = createProductDataTransformer<ProductVariation>( transformations );

	return new ModelRepository(
		restListChild< ProductVariationRepositoryParams >( buildURL, ProductVariation, httpClient, transformer ),
		restCreateChild< ProductVariationRepositoryParams >( buildURL, ProductVariation, httpClient, transformer ),
		restReadChild< ProductVariationRepositoryParams >( buildChildURL, ProductVariation, httpClient, transformer ),
		restUpdateChild< ProductVariationRepositoryParams >( buildChildURL, ProductVariation, httpClient, transformer ),
		restDeleteChild< ProductVariationRepositoryParams >( buildChildURL, httpClient ),
	);
}
