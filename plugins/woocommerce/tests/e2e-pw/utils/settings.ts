/**
 * External dependencies
 */
import e2eUtils from '@woocommerce/e2e-utils-playwright';

const { createClient, WC_API_PATH } = e2eUtils;

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';
import playwrightConfig from '../playwright.config';
import type { RestApiClient, RestApiResponse } from '../fixtures/fixtures';

/**
 * Setting value response from WooCommerce API.
 */
interface SettingResponse {
	value: string;
}

/**
 * Object containing initial and updated setting values.
 */
export interface SettingValues {
	initial: string;
	updated: string;
}

const apiClient = createClient( playwrightConfig.use?.baseURL as string, {
	type: 'basic',
	username: admin.username,
	password: admin.password,
} ) as RestApiClient;

/**
 * Resolve a settings path to the full API path.
 *
 * @param path - Relative path to the setting
 * @return Full API path
 */
function resolvePath( path: string ): string {
	return `${ WC_API_PATH }/settings/${ path }`.replace( /\/+/g, '/' );
}

/**
 * Updates the value of a setting at the specified path.
 *
 * @param path         - The API path of the setting to update. E.g. 'settings/general/woocommerce_calc_taxes'.
 * @param desiredValue - The new value to set for the setting. E.g. 'yes'.
 */
export async function updateValue(
	path: string,
	desiredValue: string
): Promise< void > {
	await apiClient
		.put( resolvePath( path ), { value: desiredValue } )
		.catch( ( err: Error ) => {
			console.error( `Error updating ${ path }` );
			throw err;
		} );
}

/**
 * Updates the value of a setting if it is different from the desired value.
 *
 * @param path         - The API path of the setting to check and update. E.g. 'settings/general/woocommerce_calc_taxes'.
 * @param desiredValue - The desired value to set for the setting. E.g. 'yes'.
 * @return A promise that resolves to an object containing the initial and updated values.
 */
export async function updateIfNeeded(
	path: string,
	desiredValue: string
): Promise< SettingValues > {
	const response: RestApiResponse = await apiClient
		.get( resolvePath( path ) )
		.catch( ( err: Error ) => {
			console.log( `Error checking ${ path }` );
			throw err;
		} );
	const initialValue = ( response.data as SettingResponse ).value;
	if ( initialValue !== desiredValue ) {
		await updateValue( path, desiredValue );
	}
	return { initial: initialValue, updated: desiredValue };
}

/**
 * Resets the value of a setting to its initial value if it was changed.
 *
 * @param path   - The API path of the setting to reset. E.g. 'settings/general/woocommerce_calc_taxes'.
 * @param values - An object containing the initial and updated values of the setting. E.g. { initial: 'no', updated: 'yes' }.
 */
export async function resetValue(
	path: string,
	values: SettingValues
): Promise< void > {
	if ( values.initial !== values.updated ) {
		await updateValue( path, values.initial );
	}
}
