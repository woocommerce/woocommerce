/**
 * Internal dependencies
 */
import { encodeCredentials } from './plugin-utils';
const { admin } = require( '../test-data/data' );

export const setOption = async (
	request,
	baseURL,
	optionName,
	optionValue
) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				admin.username,
				admin.password
			) }`,
			cookie: '',
		},
	} );

	return await apiContext
		.post( '/wp-json/e2e-options/update', {
			failOnStatusCode: true,
			data: { option_name: optionName, option_value: optionValue },
		} )
		.then( ( response ) => {
			return response.json();
		} );
};
