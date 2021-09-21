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
	buildProductURL,
} from '../../../models';
import {
	createProductDataTransformer,
	createProductDeliveryTransformation,
	createProductInventoryTransformation,
	createProductPriceTransformation,
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
	const buildURL = ( parent: ModelID ) => buildProductURL( parent ) + '/variations/';
	const buildChildURL = ( parent: ModelID, id: ModelID ) => buildURL( parent ) + id;
	const buildDeleteURL = ( parent: ModelID, id: ModelID ) => buildChildURL( parent, id ) + '?force=true';

	const delivery = createProductDeliveryTransformation();
	const inventory = createProductInventoryTransformation();
	const price = createProductPriceTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const shipping = createProductShippingTransformation();
	const transformations = [
		...delivery,
		...inventory,
		...price,
		...salesTax,
		...shipping,
	];

	const transformer = createProductDataTransformer<ProductVariation>( transformations );

	return new ModelRepository(
		restListChild< ProductVariationRepositoryParams >( buildURL, ProductVariation, httpClient, transformer ),
		restCreateChild< ProductVariationRepositoryParams >( buildURL, ProductVariation, httpClient, transformer ),
		restReadChild< ProductVariationRepositoryParams >( buildChildURL, ProductVariation, httpClient, transformer ),
		restUpdateChild< ProductVariationRepositoryParams >( buildChildURL, ProductVariation, httpClient, transformer ),
		restDeleteChild< ProductVariationRepositoryParams >( buildDeleteURL, httpClient ),
	);
}
