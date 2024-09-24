const { request } = require( '@playwright/test' );
const { encodeCredentials } = require( '../utils/plugin-utils' );
const { admin } = require( '../test-data/data' );

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = async ( baseURL, theme ) => {
	const requestContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				admin.username,
				admin.password
			) }`,
			cookie: '',
		},
	} );

	return await requestContext
		.post( '/wp-json/e2e-theme/activate', {
			data: {
				theme_name: theme,
			},
		} )
		.then( ( response ) => {
			return response.json();
		} );
};
