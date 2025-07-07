// @ts-check
/**
 * External dependencies
 */
import axios from 'axios';
import OAuth from 'oauth-1.0a';
import { createHmac } from 'crypto';

/**
 * Create an API client instance with the given configuration
 *
 * @param {string} baseURL Base URL for the API
 * @param {Object} auth    Auth object: { type: 'basic', username, password } or { type: 'oauth1', consumerKey, consumerSecret }
 * @return {Object} API client instance with HTTP methods
 */
export function createClient( baseURL, auth ) {
	if ( ! auth || typeof auth !== 'object' ) {
		throw new Error( 'auth parameter is required and must be an object' );
	}
	if ( auth.type === 'basic' ) {
		if ( ! auth.username || ! auth.password ) {
			throw new Error( 'Basic auth requires username and password' );
		}
	} else if ( auth.type === 'oauth1' ) {
		if ( ! auth.consumerKey || ! auth.consumerSecret ) {
			throw new Error(
				'OAuth1 auth requires consumerKey and consumerSecret'
			);
		}
	} else {
		throw new Error( 'auth.type must be either "basic" or "oauth1"' );
	}

	// Ensure baseURL ends with '/'
	if ( ! baseURL.endsWith( '/' ) ) {
		baseURL += '/';
	}

	// Only append 'wp-json/' if not already present
	if ( ! baseURL.endsWith( 'wp-json/' ) ) {
		baseURL += 'wp-json/';
	}

	const axiosConfig = {
		baseURL,
		headers: {
			'Content-Type': 'application/json',
		},
	};

	let oauth;
	if ( auth.type === 'basic' ) {
		axiosConfig.auth = {
			username: auth.username,
			password: auth.password,
		};
	} else if ( auth.type === 'oauth1' ) {
		oauth = new OAuth( {
			consumer: {
				key: auth.consumerKey,
				secret: auth.consumerSecret,
			},
			signature_method: 'HMAC-SHA256',
			hash_function: ( base, key ) => {
				return createHmac( 'sha256', key )
					.update( base )
					.digest( 'base64' );
			},
		} );
	}

	const axiosInstance = axios.create( axiosConfig );

	function oauthRequest(
		method,
		path,
		{ params = {}, data = {}, debug = false } = {}
	) {
		let url = baseURL + path.replace( /^\//, '' );
		let requestConfig = { method };
		let oauthParams, headers;

		if ( method === 'GET' ) {
			oauthParams = oauth.authorize( {
				url,
				method,
				data: params,
			} );
			const urlObj = new URL( url );
			Object.entries( { ...params, ...oauthParams } ).forEach(
				( [ key, value ] ) => {
					urlObj.searchParams.append( key, value );
				}
			);
			url = urlObj.toString();
			requestConfig = { ...requestConfig, url };
		} else {
			const isJson = (
				axiosConfig.headers[ 'Content-Type' ] || ''
			).includes( 'application/json' );
			oauthParams = oauth.authorize( {
				url,
				method,
				data: isJson ? {} : data,
			} );
			headers = {
				...axiosConfig.headers,
				...oauth.toHeader( oauthParams ),
			};
			requestConfig = { ...requestConfig, url, headers, data };
		}

		if ( debug ) {
			console.log( 'oauthRequest', {
				method,
				url,
				params,
				data,
				headers,
			} );
		}
		return axios( requestConfig );
	}

	return {
		/**
		 * Make a GET request
		 *
		 * @param {string} path   API endpoint path
		 * @param {Object} params Query parameters
		 * @return {Promise} Promise that resolves to response object
		 */
		async get( path, params = {}, debug = false ) {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'GET', path, { params, debug } );
			}
			const response = await axiosInstance.get( path, { params } );
			if ( debug ) {
				console.log( 'get', { path, params, response } );
			}
			return response;
		},

		/**
		 * Make a POST request
		 *
		 * @param {string} path API endpoint path
		 * @param {Object} data Request body data
		 * @return {Promise} Promise that resolves to response object
		 */
		async post( path, data = {}, debug = false ) {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'POST', path, { data, debug } );
			}
			const response = await axiosInstance.post( path, data );
			if ( debug ) {
				console.log( 'post', { path, data, response } );
			}
			return response;
		},

		/**
		 * Make a PUT request
		 *
		 * @param {string} path API endpoint path
		 * @param {Object} data Request body data
		 * @return {Promise} Promise that resolves to response object
		 */
		async put( path, data = {}, debug = false ) {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'PUT', path, { data, debug } );
			}
			const response = await axiosInstance.put( path, data );
			if ( debug ) {
				console.log( 'put', { path, data, response } );
			}
			return response;
		},

		/**
		 * Make a DELETE request
		 *
		 * @param {string} path   API endpoint path
		 * @param {Object} params Query parameters or request body
		 * @return {Promise} Promise that resolves to response object
		 */
		async delete( path, params = {}, debug = false ) {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'DELETE', path, { data: params, debug } );
			}
			const response = await axiosInstance.delete( path, {
				data: params,
			} );
			if ( debug ) {
				console.log( 'delete', { path, params, response } );
			}
			return response;
		},
	};
}

export const WC_API_PATH = 'wc/v3';
export const WC_ADMIN_API_PATH = 'wc-admin';
export const WP_API_PATH = 'wp/v2';
