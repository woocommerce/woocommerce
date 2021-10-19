import { HTTPClient } from '../../../http';
import { ListsCoupons, ReadsCoupons, UpdatesCoupons, CreatesCoupons, DeletesCoupons } from '../../../models';
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {
 * CreatesCoupons|
 * ListsCoupons|
 * ReadsCoupons|
 * UpdatesCoupons |
 * DeletesCoupons
 * } The created repository.
 */
export default function couponRESTRepository(httpClient: HTTPClient): CreatesCoupons & ListsCoupons & ReadsCoupons & UpdatesCoupons & DeletesCoupons;
//# sourceMappingURL=coupon.d.ts.map