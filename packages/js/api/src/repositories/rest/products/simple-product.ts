import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	SimpleProduct,
	baseProductURL,
	buildProductURL,
	deleteProductURL,
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
	createProductPriceTransformation,
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
	const crossSells = createProductCrossSellsTransformation();
	const delivery = createProductDeliveryTransformation();
	const inventory = createProductInventoryTransformation();
	const price = createProductPriceTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const shipping = createProductShippingTransformation();
	const upsells = createProductUpSellsTransformation();
	const transformations = [
		...crossSells,
		...delivery,
		...inventory,
		...price,
		...salesTax,
		...shipping,
		...upsells,
	];

	const transformer = createProductTransformer<SimpleProduct>( 'simple', transformations );

	return new ModelRepository(
		restList< SimpleProductRepositoryParams >( baseProductURL, SimpleProduct, httpClient, transformer ),
		restCreate< SimpleProductRepositoryParams >( baseProductURL, SimpleProduct, httpClient, transformer ),
		restRead< SimpleProductRepositoryParams >( buildProductURL, SimpleProduct, httpClient, transformer ),
		restUpdate< SimpleProductRepositoryParams >( buildProductURL, SimpleProduct, httpClient, transformer ),
		restDelete< SimpleProductRepositoryParams >( deleteProductURL, httpClient ),
	);
}
