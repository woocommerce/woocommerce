/**
 * Internal dependencies
 */
import { httpClient } from './http-client';

const optionsEndpoint = '/wc-admin/options';

export async function updateOption( optionName: string, optionValue: string ) {
	const response = await httpClient.post( optionsEndpoint, {
		[ optionName ]: optionValue,
	} );
	expect( response.statusCode ).toEqual( 200 );
}
