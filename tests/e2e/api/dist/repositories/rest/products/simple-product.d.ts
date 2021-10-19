import { HTTPClient } from '../../../http';
import { CreatesSimpleProducts, DeletesSimpleProducts, ListsSimpleProducts, ReadsSimpleProducts, UpdatesSimpleProducts } from '../../../models';
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
export declare function simpleProductRESTRepository(httpClient: HTTPClient): ListsSimpleProducts & CreatesSimpleProducts & ReadsSimpleProducts & UpdatesSimpleProducts & DeletesSimpleProducts;
//# sourceMappingURL=simple-product.d.ts.map