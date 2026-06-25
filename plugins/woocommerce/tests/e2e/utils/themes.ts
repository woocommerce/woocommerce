/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { encodeCredentials } from '../utils/plugin-utils';
import { admin } from '../test-data/data';

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = async ( baseURL: string, theme: string ) => {
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

	const response = await requestContext.post(
		'./wp-json/e2e-theme/activate',
		{
			data: {
				theme_name: theme,
			},
		}
	);

	const result = await response.json();

	if ( ! response.ok() ) {
		throw new Error( `Failed to activate theme: ${ result.message }` );
	}

	return result;
};
