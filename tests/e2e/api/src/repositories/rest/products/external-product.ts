import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	ExternalProduct,
	ModelID,
	CreatesExternalProducts,
	DeletesExternalProducts,
	ListsExternalProducts,
	ReadsExternalProducts,
	ExternalProductRepositoryParams,
	UpdatesExternalProducts,
} from '../../../models';
import {
	createProductTransformer,
	createProductExternalTransformation,
	createProductSalesTaxTransformation,
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
 * 	ListsExternalProducts|
 * 	CreatesExternalProducts|
 * 	ReadsExternalProducts|
 * 	UpdatesExternalProducts|
 * 	DeletesExternalProducts
 * } The created repository.
 */
export function externalProductRESTRepository( httpClient: HTTPClient ): ListsExternalProducts
	& CreatesExternalProducts
	& ReadsExternalProducts
	& UpdatesExternalProducts
	& DeletesExternalProducts {
	const buildURL = ( id: ModelID ) => '/wc/v3/products/' + id;

	const external = createProductExternalTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const upsells = createProductUpSellsTransformation();
	const transformations = [
		...external,
		...salesTax,
		...upsells,
	];

	const transformer = createProductTransformer<ExternalProduct>( 'external', transformations );

	return new ModelRepository(
		restList< ExternalProductRepositoryParams >( () => '/wc/v3/products', ExternalProduct, httpClient, transformer ),
		restCreate< ExternalProductRepositoryParams >( () => '/wc/v3/products', ExternalProduct, httpClient, transformer ),
		restRead< ExternalProductRepositoryParams >( buildURL, ExternalProduct, httpClient, transformer ),
		restUpdate< ExternalProductRepositoryParams >( buildURL, ExternalProduct, httpClient, transformer ),
		restDelete< ExternalProductRepositoryParams >( buildURL, httpClient ),
	);
}
