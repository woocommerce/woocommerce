import { HTTPClient } from '../../../http';
import { CreatesProductVariations, DeletesProductVariations, ListsProductVariations, ReadsProductVariations, UpdatesProductVariations } from '../../../models';
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * 	ListsProductVariations|
 * 	CreatesProductVariations|
 * 	ReadsProductVariations|
 * 	UpdatesProductVariations|
 * 	DeletesProductVariations
 * } The created repository.
 */
export declare function productVariationRESTRepository(httpClient: HTTPClient): ListsProductVariations & CreatesProductVariations & ReadsProductVariations & UpdatesProductVariations & DeletesProductVariations;
//# sourceMappingURL=variation.d.ts.map