import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	VariableProduct,
	CreatesVariableProducts,
	DeletesVariableProducts,
	ListsVariableProducts,
	ReadsVariableProducts,
	VariableProductRepositoryParams,
	UpdatesVariableProducts,
	baseProductURL,
	buildProductURL,
	deleteProductURL,
} from '../../../models';
import {
	createProductTransformer,
	createProductCrossSellsTransformation,
	createProductInventoryTransformation,
	createProductSalesTaxTransformation,
	createProductShippingTransformation,
	createProductUpSellsTransformation,
	createProductVariableTransformation,
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
 * 	ListsVariableProducts|
 * 	CreatesVariableProducts|
 * 	ReadsVariableProducts|
 * 	UpdatesVariableProducts|
 * 	DeletesVariableProducts
 * } The created repository.
 */
export function variableProductRESTRepository( httpClient: HTTPClient ): ListsVariableProducts
	& CreatesVariableProducts
	& ReadsVariableProducts
	& UpdatesVariableProducts
	& DeletesVariableProducts {
	const crossSells = createProductCrossSellsTransformation();
	const inventory = createProductInventoryTransformation();
	const salesTax = createProductSalesTaxTransformation();
	const shipping = createProductShippingTransformation();
	const upsells = createProductUpSellsTransformation();
	const variable = createProductVariableTransformation();
	const transformations = [
		...crossSells,
		...inventory,
		...salesTax,
		...shipping,
		...upsells,
		...variable,
	];

	const transformer = createProductTransformer<VariableProduct>( 'variable', transformations );

	return new ModelRepository(
		restList< VariableProductRepositoryParams >( baseProductURL, VariableProduct, httpClient, transformer ),
		restCreate< VariableProductRepositoryParams >( baseProductURL, VariableProduct, httpClient, transformer ),
		restRead< VariableProductRepositoryParams >( buildProductURL, VariableProduct, httpClient, transformer ),
		restUpdate< VariableProductRepositoryParams >( buildProductURL, VariableProduct, httpClient, transformer ),
		restDelete< VariableProductRepositoryParams >( deleteProductURL, httpClient ),
	);
}
