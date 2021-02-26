import { HTTPClient } from '../../../http';
import { ModelRepository } from '../../../framework';
import {
	VariableProduct,
	ModelID,
	CreatesVariableProducts,
	DeletesVariableProducts,
	ListsVariableProducts,
	ReadsVariableProducts,
	VariableProductRepositoryParams,
	UpdatesVariableProducts,
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
// @todo add child rest methods
import {
	restCreate,
	restDelete,
	//	restDeleteChild,
	restList,
	//	restListChild,
	restRead,
	//	restReadChild,
	restUpdate,
//	restUpdateChild,
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
	// @todo child url function
	const buildURL = ( id: ModelID ) => '/wc/v3/products/' + id;

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

	// @todo create and add variation repository params
	return new ModelRepository(
		restList< VariableProductRepositoryParams >( () => '/wc/v3/products', VariableProduct, httpClient, transformer ),
		restCreate< VariableProductRepositoryParams >( () => '/wc/v3/products', VariableProduct, httpClient, transformer ),
		restRead< VariableProductRepositoryParams >( buildURL, VariableProduct, httpClient, transformer ),
		restUpdate< VariableProductRepositoryParams >( buildURL, VariableProduct, httpClient, transformer ),
		restDelete< VariableProductRepositoryParams >( buildURL, httpClient ),
	);
}
