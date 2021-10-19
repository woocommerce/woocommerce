import { HTTPClient } from '../../../http';
import { CreatesExternalProducts, DeletesExternalProducts, ListsExternalProducts, ReadsExternalProducts, UpdatesExternalProducts } from '../../../models';
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
export declare function externalProductRESTRepository(httpClient: HTTPClient): ListsExternalProducts & CreatesExternalProducts & ReadsExternalProducts & UpdatesExternalProducts & DeletesExternalProducts;
//# sourceMappingURL=external-product.d.ts.map