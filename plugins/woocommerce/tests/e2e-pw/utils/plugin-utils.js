const { APIRequest } = require( '@playwright/test' );

/**
 * Encode basic auth username and password to be used in HTTP Authorization header.
 *
 * @param {string} username
 * @param {string} password
 * @returns Base64-encoded string
 */
const encodeCredentials = ( username, password ) => {
	return Buffer.from( `${ username }:${ password }` ).toString( 'base64' );
};

/**
 * Deactivate and delete a plugin specified by the given `slug` using the WordPress API.
 *
 * @param {object} params
 * @param {APIRequest} params.request
 * @param {string} params.baseURL
 * @param {string} params.slug
 * @param {string} params.username
 * @param {string} params.password
 */
export const deletePlugin = async ( {
	request,
	baseURL,
	slug,
	username,
	password,
} ) => {
	// Check if plugin is installed.
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials( username, password ) }`,
		},
	} );

	const response = await apiContext.get( `/wp-json/wp/v2/plugins/${ slug }`, {
		failOnStatusCode: true,
	} );

	// If installed, deactivate and delete it.
	if ( response.ok() ) {
		await apiContext.put( `/wp-json/wp/v2/plugins/${ slug }`, {
			data: { status: 'inactive' },
			failOnStatusCode: true,
		} );

		await apiContext.delete( `/wp-json/wp/v2/plugins/${ slug }`, {
			failOnStatusCode: true,
		} );
	}

	// Dispose all responses.
	await apiContext.dispose();
};
