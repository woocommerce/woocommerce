/**
 * Internal dependencies
 */
import { httpClient } from './http-client';

const optionsEndpoint = '/wc-admin/options';

export async function updateOption(
	optionName: string,
	optionValue: string
): Promise< void > {
	const response = await httpClient.post( optionsEndpoint, {
		[ optionName ]: optionValue,
	} );
	expect( response.statusCode ).toEqual( 200 );
}

export async function unhideTaskList( id: string ): Promise< void > {
	const response = await httpClient.post(
		`/wc-admin/onboarding/tasks/${ id }/unhide`
	);
	expect( response.statusCode ).toEqual( 200 );
}
