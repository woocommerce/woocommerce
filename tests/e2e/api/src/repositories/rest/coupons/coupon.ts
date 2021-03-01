import { HTTPClient } from '../../../http';
import {
	ModelRepository,
} from '../../../framework';
import {
	ModelID,
	Coupon,
	CouponRepositoryParams,
	ListsCoupons,
	ReadsCoupons,
	UpdatesCoupons,
	CreatesCoupons,
	DeletesCoupons,
} from '../../../models';

import {
	restList,
	restCreate,
	restRead,
	restUpdate,
	restDelete,
} from '../shared';
import { createCouponTransformer } from './transformer';

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
export default function couponRESTRepository( httpClient: HTTPClient ): CreatesCoupons
	& ListsCoupons
	& ReadsCoupons
	& UpdatesCoupons
	& DeletesCoupons {
	const buildURL = ( id: ModelID ) => '/wc/v3/coupons/' + id;
	// Using `?force=true` permanently deletes the coupon
	const buildDeleteUrl = ( id: ModelID ) => `/wc/v3/coupons/${ id }?force=true`;

	const transformer = createCouponTransformer();

	return new ModelRepository(
		restList< CouponRepositoryParams >( () => '/wc/v3/coupons', Coupon, httpClient, transformer ),
		restCreate< CouponRepositoryParams >( () => '/wc/v3/coupons', Coupon, httpClient, transformer ),
		restRead< CouponRepositoryParams >( buildURL, Coupon, httpClient, transformer ),
		restUpdate< CouponRepositoryParams >( buildURL, Coupon, httpClient, transformer ),
		restDelete< CouponRepositoryParams >( buildDeleteUrl, httpClient ),
	);
}
