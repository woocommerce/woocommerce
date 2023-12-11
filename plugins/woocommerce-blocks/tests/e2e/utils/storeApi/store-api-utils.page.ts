/**
 * External dependencies
 */

import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */

export class StoreApiUtils {
	private requestUtils: RequestUtils;

	constructor( requestUtils: RequestUtils ) {
		this.requestUtils = requestUtils;
	}

	// @todo: It is necessary work to a middleware to avoid this kind of code.
	async cleanCart() {
		const response = await this.requestUtils.request.get(
			'/wp-json/wc/store/cart'
		);

		const { nonce } = response.headers();

		await this.requestUtils.request.delete(
			`/wp-json/wc/store/v1/cart/items`,
			{
				headers: {
					nonce,
				},
			}
		);
	}
}
