import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	SimpleProduct,
	ModelID,
	CreatesSimpleProducts,
	DeletesSimpleProducts,
	ListsSimpleProducts,
	ReadsSimpleProducts,
	SimpleProductRepositoryParams,
	UpdatesSimpleProducts,
} from '../../../models';
import {
	createProductTransformer,
	createProductCrossSellsTransformation,
	createProductDeliveryTransformation,
	createProductInventoryTransformation,
	createProductSalesTaxTransformation,
	createProductShippingTransformation,
	createProductUpSellsTransformation,
} from './shared';
import {
	restCreate,
	restDelete,
	restList,
	restRead,
	restUpdate,
} from '../shared';

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsSimpleProducts|
 * 	CreatesSimpleProducts|
 * 	ReadsSimpleProducts|
 * 	UpdatesSimpleProducts|
 * 	DeletesSimpleProducts
 * } The created repository.
 */
export function simpleProductRESTRepository( httpClient: HTTPClient ): ListsSimpleProducts
	& CreatesSimpleProducts
	& ReadsSimpleProducts
	& UpdatesSimpleProducts
	& DeletesSimpleProducts {
	const buildURL = ( id: ModelID ) => '/wc/v3/products/' + id;

	const crossSells = createProductCrossSellsTransformation();
	const delivery = createProductDeliveryTransformation();
	const inventory = createProductInventoryTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const shipping = createProductShippingTransformation();
	const upsells = createProductUpSellsTransformation();
	const transformations = [
		...crossSells,
		...delivery,
		...inventory,
		...salesTax,
		...shipping,
		...upsells,
	];

	const transformer = createProductTransformer<SimpleProduct>( 'simple', transformations );

	return new ModelRepository(
		restList< SimpleProductRepositoryParams >( () => '/wc/v3/products', SimpleProduct, httpClient, transformer ),
		restCreate< SimpleProductRepositoryParams >( () => '/wc/v3/products', SimpleProduct, httpClient, transformer ),
		restRead< SimpleProductRepositoryParams >( buildURL, SimpleProduct, httpClient, transformer ),
		restUpdate< SimpleProductRepositoryParams >( buildURL, SimpleProduct, httpClient, transformer ),
		restDelete< SimpleProductRepositoryParams >( buildURL, httpClient ),
	);
}
