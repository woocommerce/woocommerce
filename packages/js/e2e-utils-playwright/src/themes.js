const { request } = require( '@playwright/test' );

import { encodeCredentials } from './plugin-utils';

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = async ( baseURL, theme, adminCredentials = {} ) => {
	const requestContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				adminCredentials.username,
				adminCredentials.password
			) }`,
			cookie: '',
		},
	} );

	const response = await requestContext.post( '/wp-json/e2e-theme/activate', {
		data: {
			theme_name: theme,
		},
	} );

	const result = await response.json();

	if ( ! response.ok() ) {
		throw new Error( `Failed to activate theme: ${ result.message }` );
	}

	return result;
};
