/**
 * External dependencies
 */
import { HTTPClientFactory } from '@woocommerce/api';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';

class ApiClient {
	static instance;

	static getInstance() {
		if ( ! ApiClient.instance ) {
			ApiClient.instance = ApiClient.create();
		}
		return ApiClient.instance;
	}

	static create() {
		let baseURL = playwrightConfig.use.baseURL;
		if ( ! baseURL.endsWith( '/' ) ) {
			baseURL += '/';
		}
		return HTTPClientFactory.build( baseURL )
			.withBasicAuth( admin.username, admin.password )
			.withIndexPermalinks()
			.create();
	}
}

export const WC_API_PATH = 'wc/v3';
export const WC_ADMIN_API_PATH = 'wc-admin';
export const WP_API_PATH = 'wp/v2';

export default ApiClient;
