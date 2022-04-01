import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	baseProductURL,
	buildProductURL,
	deleteProductURL,
	ExternalProduct,
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
	createProductPriceTransformation,
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
	const external = createProductExternalTransformation();
	const price = createProductPriceTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const upsells = createProductUpSellsTransformation();
	const transformations = [
		...external,
		...price,
		...salesTax,
		...upsells,
	];

	const transformer = createProductTransformer<ExternalProduct>( 'external', transformations );

	return new ModelRepository(
		restList< ExternalProductRepositoryParams >( baseProductURL, ExternalProduct, httpClient, transformer ),
		restCreate< ExternalProductRepositoryParams >( baseProductURL, ExternalProduct, httpClient, transformer ),
		restRead< ExternalProductRepositoryParams >( buildProductURL, ExternalProduct, httpClient, transformer ),
		restUpdate< ExternalProductRepositoryParams >( buildProductURL, ExternalProduct, httpClient, transformer ),
		restDelete< ExternalProductRepositoryParams >( deleteProductURL, httpClient ),
	);
}
