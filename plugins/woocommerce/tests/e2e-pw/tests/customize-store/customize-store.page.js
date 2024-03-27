const { encodeCredentials } = require( '../../utils/plugin-utils' );

export class CustomizeStorePage {
	request;
	constructor( { request } ) {
		this.request = request;
	}

	async resetCustomizeStoreChanges( baseURL ) {
		const apiContext = await this.request.newContext( {
			baseURL,
			extraHTTPHeaders: {
				Authorization: `Basic ${ encodeCredentials(
					'admin',
					'password'
				) }`,
				cookie: '',
			},
		} );

		await apiContext.post(
			'/wp-json/wc-admin-test-helper/tools/reset-cys'
		);
	}
}
