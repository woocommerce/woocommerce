import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	GroupedProduct,
	CreatesGroupedProducts,
	DeletesGroupedProducts,
	ListsGroupedProducts,
	ReadsGroupedProducts,
	GroupedProductRepositoryParams,
	UpdatesGroupedProducts,
	baseProductURL,
	buildProductURL,
	deleteProductURL,
} from '../../../models';
import {
	createProductTransformer,
	createProductGroupedTransformation,
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
 * 	ListsGroupedProducts|
 * 	CreatesGroupedProducts|
 * 	ReadsGroupedProducts|
 * 	UpdatesGroupedProducts|
 * 	DeletesGroupedProducts
 * } The created repository.
 */
export function groupedProductRESTRepository( httpClient: HTTPClient ): ListsGroupedProducts
	& CreatesGroupedProducts
	& ReadsGroupedProducts
	& UpdatesGroupedProducts
	& DeletesGroupedProducts {
	const upsells = createProductUpSellsTransformation();
	const grouped = createProductGroupedTransformation();
	const transformations = [
		...upsells,
		...grouped,
	];

	const transformer = createProductTransformer<GroupedProduct>( 'grouped', transformations );

	return new ModelRepository(
		restList< GroupedProductRepositoryParams >( baseProductURL, GroupedProduct, httpClient, transformer ),
		restCreate< GroupedProductRepositoryParams >( baseProductURL, GroupedProduct, httpClient, transformer ),
		restRead< GroupedProductRepositoryParams >( buildProductURL, GroupedProduct, httpClient, transformer ),
		restUpdate< GroupedProductRepositoryParams >( buildProductURL, GroupedProduct, httpClient, transformer ),
		restDelete< GroupedProductRepositoryParams >( deleteProductURL, httpClient ),
	);
}
