import { AdapterTypes, ModelRegistry } from './framework/model-registry';
import { APIAdapter } from './framework/api/api-adapter';
import { AxiosAPIService } from './framework/api/axios/axios-api-service';

/**
 * Initializes all of the APIAdapters with a client to communicate with the API.
 *
 * @param {ModelRegistry} registry The model registry that we want to initialize.
 * @param {string}        apiURL The base URL for the API.
 * @param {string}        consumerKey The OAuth consumer key for the API service.
 * @param {string}        consumerSecret The OAuth consumer secret for the API service.
 */
export function initializeUsingOAuth(
	registry: ModelRegistry,
	apiURL: string,
	consumerKey: string,
	consumerSecret: string,
): void {
	const adapters = registry.getAdapters( AdapterTypes.API ) as APIAdapter< any >[];
	if ( ! adapters.length ) {
		return;
	}

	const apiService = AxiosAPIService.createUsingOAuth( apiURL, consumerKey, consumerSecret );
	for ( const adapter of adapters ) {
		adapter.setAPIService( apiService );
	}
}

/**
 * Initialize all of the APIAdapters with a client to communicate with the API.
 *
 *
 *
 * @param {ModelRegistry} registry The model registry that we want to initialize.
 * @param {string}        apiURL The base URL for the API.
 * @param {string}        username The username to use for authentication.
 * @param {string}        password The password to use for authentication.
 */
export function initializeUsingBasicAuth(
	registry: ModelRegistry,
	apiURL: string,
	username: string,
	password: string,
): void {
	const adapters = registry.getAdapters( AdapterTypes.API ) as APIAdapter< any >[];
	if ( ! adapters.length ) {
		return;
	}

	const apiService = AxiosAPIService.createUsingBasicAuth( apiURL, username, password );
	for ( const adapter of adapters ) {
		adapter.setAPIService( apiService );
	}
}
