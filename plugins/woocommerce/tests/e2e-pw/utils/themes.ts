/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { encodeCredentials } from './plugin-utils';
import { admin } from '../test-data/data';

/**
 * Theme activation response interface.
 */
interface ThemeActivationResponse {
	success: boolean;
	message?: string;
	theme?: string;
}

/**
 * Default theme slug.
 */
export const DEFAULT_THEME = 'twentytwentythree';

/**
 * Activate a theme using the REST API.
 *
 * @param baseURL - The base URL of the WordPress site.
 * @param theme   - The theme slug to activate.
 * @return The activation response.
 */
export const activateTheme = async (
	baseURL: string,
	theme: string
): Promise< ThemeActivationResponse > => {
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

	const result = ( await response.json() ) as ThemeActivationResponse;

	if ( ! response.ok() ) {
		throw new Error( `Failed to activate theme: ${ result.message }` );
	}

	return result;
};
