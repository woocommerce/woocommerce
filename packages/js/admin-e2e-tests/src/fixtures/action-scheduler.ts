/**
 * Internal dependencies
 */
import { httpClient } from './http-client';

const actionSchedulerEndpoint = '/woocommerce-reset/v1/cron/run';

export async function runActionScheduler() {
	const response = await httpClient.post( actionSchedulerEndpoint );
	if ( response.statusCode !== 404 ) {
		expect( response.statusCode ).toEqual( 200 );
	}
}
