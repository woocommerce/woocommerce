/**
 * External dependencies
 */
import axios, { AxiosRequestConfig, AxiosResponse } from 'axios';
import OAuth from 'oauth-1.0a';
import { createHmac } from 'crypto';

/**
 * Internal dependencies
 */
import type { Auth, ApiClient } from './types';

// Re-export types for consumers
export type { BasicAuth, OAuth1Auth, Auth } from './types';

interface OAuthRequestOptions {
	params?: Record< string, unknown >;
	data?: Record< string, unknown >;
	debug?: boolean;
}

/**
 * Create an API client instance with the given configuration.
 *
 * @param baseURL - Base URL for the API
 * @param auth    - Auth object: { type: 'basic', username, password } or { type: 'oauth1', consumerKey, consumerSecret }
 * @return API client instance with HTTP methods
 */
export function createClient( baseURL: string, auth: Auth ): ApiClient {
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
	let normalizedBaseURL = baseURL;
	if ( ! normalizedBaseURL.endsWith( '/' ) ) {
		normalizedBaseURL += '/';
	}

	// Only append 'wp-json/' if not already present
	if ( ! normalizedBaseURL.endsWith( 'wp-json/' ) ) {
		normalizedBaseURL += 'wp-json/';
	}

	const axiosConfig: AxiosRequestConfig = {
		baseURL: normalizedBaseURL,
		headers: {
			'Content-Type': 'application/json',
		},
	};

	let oauth: OAuth | undefined;
	if ( auth.type === 'basic' ) {
		axiosConfig.auth = {
			username: auth.username,
			password: auth.password,
		};

		// Warn if Basic Auth is used over HTTP, except for localhost
		const isHttp = normalizedBaseURL.startsWith( 'http://' );
		const isLocalhost =
			normalizedBaseURL.startsWith( 'http://localhost' ) ||
			normalizedBaseURL.startsWith( 'http://127.0.0.1' );
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
			hash_function: ( base: string, key: string ) => {
				return createHmac( 'sha256', key )
					.update( base )
					.digest( 'base64' );
			},
		} );
	}

	const axiosInstance = axios.create( axiosConfig );

	/**
	 * Utility to redact sensitive fields from logs.
	 *
	 * @param obj  - Object to redact
	 * @param keys - Keys to redact
	 * @return Redacted object
	 */
	function redact(
		obj: Record< string, unknown > | null | undefined,
		keys: string[] = [
			'password',
			'token',
			'authorization',
			'cookie',
			'secret',
		]
	): Record< string, unknown > | null | undefined {
		const shouldRedact = process.env.CI === 'true';
		if ( ! shouldRedact ) return obj;
		if ( ! obj || typeof obj !== 'object' ) return obj;
		return Object.fromEntries(
			Object.entries( obj ).map( ( [ k, v ] ) =>
				keys.includes( k.toLowerCase() )
					? [ k, '********' ]
					: [
							k,
							typeof v === 'object'
								? redact( v as Record< string, unknown >, keys )
								: v,
					  ]
			)
		);
	}

	/**
	 * Centralized logging for requests, with redaction and formatting.
	 *
	 * @param label   - Log label
	 * @param details - Details to log
	 */
	function logRequest( label: string, details: Record< string, unknown > ) {
		const redacted = Object.fromEntries(
			Object.entries( details ).map( ( [ k, v ] ) => [
				k,
				redact( v as Record< string, unknown > ),
			] )
		);
		console.log( `[${ new Date().toISOString() }] ${ label }`, redacted );
	}

	/**
	 * Make an OAuth-authenticated request.
	 *
	 * @param method         - HTTP method
	 * @param path           - API endpoint path
	 * @param options        - Request options
	 * @param options.params - Query parameters
	 * @param options.data   - Request body data
	 * @param options.debug  - Enable debug logging
	 * @return Promise resolving to the response
	 */
	function oauthRequest(
		method: string,
		path: string,
		{ params = {}, data = {}, debug = false }: OAuthRequestOptions = {}
	): Promise< AxiosResponse > {
		if ( ! oauth ) {
			throw new Error( 'OAuth not initialized' );
		}

		let url = normalizedBaseURL + path.replace( /^\//, '' );
		let requestConfig: AxiosRequestConfig = { method };
		let oauthParams: OAuth.Authorization;
		let headers: Record< string, string > | undefined;

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
					urlObj.searchParams.append( key, String( value ) );
				}
			);
			url = urlObj.toString();
			requestConfig = { ...requestConfig, url };
		} else {
			// For POST/PUT/DELETE, sign the body if form-encoded, otherwise sign as if body is empty (for JSON)
			const contentType =
				(
					axiosConfig.headers as Record< string, string > | undefined
				 )?.[ 'Content-Type' ] || '';
			const isJson = contentType.includes( 'application/json' );
			oauthParams = oauth.authorize( {
				url,
				method,
				data: isJson ? {} : data,
			} );
			headers = {
				...( axiosConfig.headers as Record< string, string > ),
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
		 * Make a GET request.
		 *
		 * @param path   - API endpoint path
		 * @param params - Query parameters
		 * @param debug  - Enable debug logging
		 * @return Promise that resolves to response object
		 */
		async get< T = unknown >(
			path: string,
			params: Record< string, unknown > = {},
			debug = false
		): Promise< AxiosResponse< T > > {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'GET', path, {
					params,
					debug,
				} );
			}
			const response = await axiosInstance.get< T >( path, { params } );
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
		 * Make a POST request.
		 *
		 * @param path  - API endpoint path
		 * @param data  - Request body data
		 * @param debug - Enable debug logging
		 * @return Promise that resolves to response object
		 */
		async post< T = unknown >(
			path: string,
			data: Record< string, unknown > = {},
			debug = false
		): Promise< AxiosResponse< T > > {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'POST', path, {
					data,
					debug,
				} );
			}
			const response = await axiosInstance.post< T >( path, data );
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
		 * Make a PUT request.
		 *
		 * @param path  - API endpoint path
		 * @param data  - Request body data
		 * @param debug - Enable debug logging
		 * @return Promise that resolves to response object
		 */
		async put< T = unknown >(
			path: string,
			data: Record< string, unknown > = {},
			debug = false
		): Promise< AxiosResponse< T > > {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'PUT', path, {
					data,
					debug,
				} );
			}
			const response = await axiosInstance.put< T >( path, data );
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
		 * Make a DELETE request.
		 *
		 * @param path   - API endpoint path
		 * @param params - Query parameters or request body
		 * @param debug  - Enable debug logging
		 * @return Promise that resolves to response object
		 */
		async delete< T = unknown >(
			path: string,
			params: Record< string, unknown > = {},
			debug = false
		): Promise< AxiosResponse< T > > {
			if ( auth.type === 'oauth1' ) {
				return oauthRequest( 'DELETE', path, {
					data: params,
					debug,
				} );
			}
			const response = await axiosInstance.delete< T >( path, {
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
