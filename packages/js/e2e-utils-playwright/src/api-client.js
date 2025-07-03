/**
 * External dependencies
 */
import axios from 'axios';

/**
 * Create an API client instance with the given configuration
 *
 * @param {string} baseURL  Base URL for the API (should end with wp-json/)
 * @param {string} username Username for basic authentication
 * @param {string} password Password for basic authentication
 * @return {Object} API client instance with HTTP methods
 */
export function createClient( baseURL, username, password ) {
	console.log( 'createClient', { baseURL } );
	// Ensure baseURL ends with '/'
	if ( ! baseURL.endsWith( '/' ) ) {
		baseURL += '/';
	}

	// Only append 'wp-json/' if not already present
	if ( ! baseURL.endsWith( 'wp-json/' ) ) {
		baseURL += 'wp-json/';
	}

	// Create axios instance with default configuration
	const axiosInstance = axios.create( {
		baseURL,
		auth: {
			username,
			password,
		},
		headers: {
			'Content-Type': 'application/json',
		},
	} );

	return {
		/**
		 * Make a GET request
		 *
		 * @param {string} path   API endpoint path
		 * @param {Object} params Query parameters
		 * @return {Promise} Promise that resolves to response object
		 */
		async get( path, params = {} ) {
			// console.log( 'get', { path, params } );
			const response = await axiosInstance.get( path, { params } );
			return response;
		},

		/**
		 * Make a POST request
		 *
		 * @param {string} path API endpoint path
		 * @param {Object} data Request body data
		 * @return {Promise} Promise that resolves to response object
		 */
		async post( path, data = {} ) {
			// console.log( 'post', { path, data } );
			const response = await axiosInstance.post( path, data );
			return response;
		},

		/**
		 * Make a PUT request
		 *
		 * @param {string} path API endpoint path
		 * @param {Object} data Request body data
		 * @return {Promise} Promise that resolves to response object
		 */
		async put( path, data = {} ) {
			// console.log( 'put', { path, data } );
			const response = await axiosInstance.put( path, data );
			return response;
		},

		/**
		 * Make a DELETE request
		 *
		 * @param {string} path   API endpoint path
		 * @param {Object} params Query parameters or request body
		 * @return {Promise} Promise that resolves to response object
		 */
		async delete( path, params = {} ) {
			// console.log( 'delete', { path, params } );
			const response = await axiosInstance.delete( path, {
				data: params,
			} );
			return response;
		},
	};
}

export const WC_API_PATH = 'wc/v3';
export const WC_ADMIN_API_PATH = 'wc-admin';
export const WP_API_PATH = 'wp/v2';
