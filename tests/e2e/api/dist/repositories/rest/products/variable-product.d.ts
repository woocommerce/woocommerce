import { HTTPClient } from '../../../http';
import { CreatesVariableProducts, DeletesVariableProducts, ListsVariableProducts, ReadsVariableProducts, UpdatesVariableProducts } from '../../../models';
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
export declare function variableProductRESTRepository(httpClient: HTTPClient): ListsVariableProducts & CreatesVariableProducts & ReadsVariableProducts & UpdatesVariableProducts & DeletesVariableProducts;
//# sourceMappingURL=variable-product.d.ts.map