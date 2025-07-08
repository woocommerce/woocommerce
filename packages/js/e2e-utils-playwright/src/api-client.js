/* eslint-disable jsdoc/check-property-names */
// @ts-check
/**
 * @typedef {Object} BasicAuth
 * @property {'basic'}  type           Type of authentication ('basic')
 * @property {string}   username       Username for basic authentication
 * @property {string}   password       Password for basic authentication
 *
 * @typedef {Object} OAuth1Auth
 * @property {'oauth1'} type           Type of authentication ('oauth1')
 * @property {string}   consumerKey    OAuth1 consumer key
 * @property {string}   consumerSecret OAuth1 consumer secret
 *
 * @typedef {BasicAuth|OAuth1Auth} Auth
 */
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

		// Warn if Basic Auth is used over HTTP, except for localhost
		const isHttp = baseURL.startsWith( 'http' );
		const isLocalhost =
			baseURL.startsWith( 'http://localhost' ) ||
			baseURL.startsWith( 'http://127.0.0.1' );
		if ( isHttp && ! isLocalhost ) {
			console.warn(
				'Warning: Using Basic Auth over HTTP exposes credentials in plaintext!'
			);
		}
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

	// Utility to redact sensitive fields from logs
	function redact(
		obj,
		keys = [ 'password', 'token', 'authorization', 'cookie', 'secret' ]
	) {
		const shouldRedact = process.env.CI === 'true';
		if ( ! shouldRedact ) return obj;
		if ( ! obj || typeof obj !== 'object' ) return obj;
		return Object.fromEntries(
			Object.entries( obj ).map( ( [ k, v ] ) =>
				keys.includes( k.toLowerCase() )
					? [ k, '********' ]
					: [ k, typeof v === 'object' ? redact( v, keys ) : v ]
			)
		);
	}

	// Centralized logging for requests, with redaction and formatting
	function logRequest( label, details ) {
		const redacted = Object.fromEntries(
			Object.entries( details ).map( ( [ k, v ] ) => [ k, redact( v ) ] )
		);
		console.log( `[${ new Date().toISOString() }] ${ label }`, redacted );
	}

	function oauthRequest(
		method,
		path,
		{ params = {}, data = {}, debug = false } = {}
	) {
		let url = baseURL + path.replace( /^\//, '' );
		let requestConfig = { method };
		let oauthParams, headers;

		if ( method === 'GET' ) {
			// For GET, sign the query params and append both params and OAuth params to the URL
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
			// For POST/PUT/DELETE, sign the body if form-encoded, otherwise sign as if body is empty (for JSON)
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
			logRequest( 'oauthRequest', {
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
				logRequest( 'get', {
					path,
					params,
					status: response?.status,
					data: response?.data,
				} );
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
				logRequest( 'post', {
					path,
					data,
					status: response?.status,
					response: response?.data,
				} );
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
				logRequest( 'put', {
					path,
					data,
					status: response?.status,
					response: response?.data,
				} );
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
				logRequest( 'delete', {
					path,
					params,
					status: response?.status,
					response: response?.data,
				} );
			}
			return response;
		},
	};
}

export const WC_API_PATH = 'wc/v3';
export const WC_ADMIN_API_PATH = 'wc-admin';
export const WP_API_PATH = 'wp/v2';
