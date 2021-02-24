import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	GroupedProduct,
	ModelID,
	CreatesGroupedProducts,
	DeletesGroupedProducts,
	ListsGroupedProducts,
	ReadsGroupedProducts,
	GroupedProductRepositoryParams,
	UpdatesGroupedProducts,
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
	// @todo replace this once https://github.com/woocommerce/woocommerce/pull/29198 is merged.
	const buildURL = ( id: ModelID ) => '/wc/v3/products/' + id;

	const upsells = createProductUpSellsTransformation();
	const grouped = createProductGroupedTransformation();
	const transformations = [
		...upsells,
		...grouped,
	];

	const transformer = createProductTransformer<GroupedProduct>( 'grouped', transformations );

	return new ModelRepository(
		restList< GroupedProductRepositoryParams >( () => '/wc/v3/products', GroupedProduct, httpClient, transformer ),
		restCreate< GroupedProductRepositoryParams >( () => '/wc/v3/products', GroupedProduct, httpClient, transformer ),
		restRead< GroupedProductRepositoryParams >( buildURL, GroupedProduct, httpClient, transformer ),
		restUpdate< GroupedProductRepositoryParams >( buildURL, GroupedProduct, httpClient, transformer ),
		restDelete< GroupedProductRepositoryParams >( buildURL, httpClient ),
	);
}
