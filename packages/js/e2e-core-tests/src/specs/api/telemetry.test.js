/**
 * Internal dependencies
 */
const { HTTPClientFactory } = require( '@woocommerce/api' );

/**
 * External dependencies
 */
const config = require( 'config' );
const { it, describe, beforeAll } = require( '@jest/globals' );

/**
 * Create the default coupon and tests interactions with it via the API.
 */
const runTelemetryAPITest = () => {
	describe( 'REST API > Telemetry', () => {
		let client;

		beforeAll( async () => {
			const admin = config.get( 'users.admin' );
			const url = config.get( 'url' );

			client = HTTPClientFactory.build( url )
				.withBasicAuth( admin.username, admin.password )
				.withIndexPermalinks()
				.create();
		} );

		it.each( [ null, {}, { platform: 'ios' }, { version: '1.1' } ] )(
			'errors for invalid request body - %p',
			async ( data ) => {
				const response = await client
					.post( `/wc-telemetry/tracker`, data )
					.catch( ( err ) => {
						expect( err.statusCode ).toBe( 400 );
					} );

				expect( response ).toBeUndefined();
			}
		);

		it( 'returns 200 with correct fields', async () => {
			const response = await client.post( `/wc-telemetry/tracker`, {
				platform: 'ios',
				version: '1.0',
			} );

			expect( response.statusCode ).toBe( 200 );
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runTelemetryAPITest;
