import { HTTPClient } from '../../../http';
import { CreatesGroupedProducts, DeletesGroupedProducts, ListsGroupedProducts, ReadsGroupedProducts, UpdatesGroupedProducts } from '../../../models';
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
export declare function groupedProductRESTRepository(httpClient: HTTPClient): ListsGroupedProducts & CreatesGroupedProducts & ReadsGroupedProducts & UpdatesGroupedProducts & DeletesGroupedProducts;
//# sourceMappingURL=grouped-product.d.ts.map