// @ts-check
/**
 * External dependencies
 */
import axios from 'axios';
import OAuth from 'oauth-1.0a';
import crypto from 'crypto';

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
			signature_method: 'HMAC-SHA1',
			hash_function( base_string, key ) {
				return crypto
					.createHmac( 'sha1', key )
					.update( base_string )
					.digest( 'base64' );
			},
		} );
	}

	const axiosInstance = axios.create( axiosConfig );

	function getSignedUrl( path, params = {} ) {
		const url = baseURL + path.replace( /^\//, '' );
		const oauthParams = oauth.authorize( {
			url,
			method: 'GET',
			data: params,
		} );
		const urlObj = new URL( url );
		Object.entries( { ...params, ...oauthParams } ).forEach(
			( [ key, value ] ) => {
				urlObj.searchParams.append( key, value );
			}
		);
		return urlObj.toString();
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
				const url = getSignedUrl( path, params );
				const response = await axios.get( url );
				if ( debug ) {
					console.log( 'get', { path, params, response } );
				}
				return response;
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
				const url = baseURL + path.replace( /^\//, '' );
				const oauthParams = oauth.authorize( {
					url,
					method: 'POST',
					data,
				} );
				const headers = {
					...axiosConfig.headers,
					...oauth.toHeader( oauthParams ),
				};

				console.log( 'post', { url, data, headers } );

				const response = await axios.post( url, data, { headers } );
				if ( debug ) {
					console.log( 'post', { path, data, response } );
				}
				return response;
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
				const url = baseURL + path.replace( /^\//, '' );
				const oauthParams = oauth.authorize( {
					url,
					method: 'PUT',
					data,
				} );
				const headers = {
					...axiosConfig.headers,
					...oauth.toHeader( oauthParams ),
				};
				const response = await axios.put( url, data, { headers } );
				if ( debug ) {
					console.log( 'put', { path, data, response } );
				}
				return response;
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
				const url = baseURL + path.replace( /^\//, '' );
				const oauthParams = oauth.authorize( {
					url,
					method: 'DELETE',
					data: params,
				} );
				const headers = {
					...axiosConfig.headers,
					...oauth.toHeader( oauthParams ),
				};
				const response = await axios.delete( url, {
					headers,
					data: params,
				} );
				if ( debug ) {
					console.log( 'delete', { path, params, response } );
				}
				return response;
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
