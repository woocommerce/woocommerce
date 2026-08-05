/**
 * External dependencies
 */
import type { Page, FrameLocator } from '@playwright/test';
import type { AxiosResponse } from 'axios';

/**
 * Authentication configuration for Basic Auth.
 *
 * @property {'basic'} type     - Type of authentication ('basic')
 * @property {string}  username - Username for basic authentication
 * @property {string}  password - Password for basic authentication
 */
export interface BasicAuth {
	type: 'basic';
	username: string;
	password: string;
}

/**
 * Authentication configuration for OAuth 1.0a.
 *
 * @property {'oauth1'} type           - Type of authentication ('oauth1')
 * @property {string}   consumerKey    - OAuth1 consumer key
 * @property {string}   consumerSecret - OAuth1 consumer secret
 */
export interface OAuth1Auth {
	type: 'oauth1';
	consumerKey: string;
	consumerSecret: string;
}

/**
 * Union type for all supported authentication methods.
 */
export type Auth = BasicAuth | OAuth1Auth;

/**
 * API Client interface returned by createClient.
 */
export interface ApiClient {
	/**
	 * Make a GET request.
	 *
	 * @template T - The expected response data type
	 * @param    path   - API endpoint path
	 * @param    params - Query parameters
	 * @param    debug  - Enable debug logging
	 * @return Promise resolving to the response
	 */
	get< T = unknown >(
		path: string,
		params?: Record< string, unknown >,
		debug?: boolean
	): Promise< AxiosResponse< T > >;

	/**
	 * Make a POST request.
	 *
	 * @template T - The expected response data type
	 * @param    path  - API endpoint path
	 * @param    data  - Request body data
	 * @param    debug - Enable debug logging
	 * @return Promise resolving to the response
	 */
	post< T = unknown >(
		path: string,
		data?: Record< string, unknown >,
		debug?: boolean
	): Promise< AxiosResponse< T > >;

	/**
	 * Make a PUT request.
	 *
	 * @template T - The expected response data type
	 * @param    path  - API endpoint path
	 * @param    data  - Request body data
	 * @param    debug - Enable debug logging
	 * @return Promise resolving to the response
	 */
	put< T = unknown >(
		path: string,
		data?: Record< string, unknown >,
		debug?: boolean
	): Promise< AxiosResponse< T > >;

	/**
	 * Make a DELETE request.
	 *
	 * @template T - The expected response data type
	 * @param    path   - API endpoint path
	 * @param    params - Query parameters or request body
	 * @param    debug  - Enable debug logging
	 * @return Promise resolving to the response
	 */
	delete< T = unknown >(
		path: string,
		params?: Record< string, unknown >,
		debug?: boolean
	): Promise< AxiosResponse< T > >;
}

/**
 * Checkout details for block-based checkout forms.
 *
 * @property {string}  [country]      - The country code (e.g., 'US')
 * @property {string}  [firstName]    - The first name
 * @property {string}  [lastName]     - The last name
 * @property {string}  [address]      - The street address
 * @property {string}  [zip]          - The ZIP or postal code
 * @property {string}  [city]         - The city
 * @property {string}  [state]        - The state
 * @property {string}  [suburb]       - The suburb (for applicable countries)
 * @property {string}  [province]     - The province (for applicable countries)
 * @property {string}  [district]     - The district (for applicable countries)
 * @property {string}  [department]   - The department (for applicable countries)
 * @property {string}  [region]       - The region (for applicable countries)
 * @property {string}  [parish]       - The parish (for applicable countries)
 * @property {string}  [county]       - The county (for applicable countries)
 * @property {string}  [prefecture]   - The prefecture (for applicable countries)
 * @property {string}  [municipality] - The municipality (for applicable countries)
 * @property {string}  [phone]        - The phone number
 * @property {boolean} [isPostalCode] - If true, search by 'Postal code' instead of 'ZIP Code'
 */
export interface CheckoutDetails {
	country?: string;
	firstName?: string;
	lastName?: string;
	address?: string;
	zip?: string;
	city?: string;
	state?: string;
	suburb?: string;
	province?: string;
	district?: string;
	department?: string;
	region?: string;
	parish?: string;
	county?: string;
	prefecture?: string;
	municipality?: string;
	phone?: string;
	isPostalCode?: boolean;
}

/**
 * Address type for checkout forms.
 */
export type AddressType = 'shipping' | 'billing';

/**
 * Page context for editor functions that receive a page object.
 */
export interface PageContext {
	page: Page;
}

/**
 * Canvas type - either FrameLocator for iframe-based editor or Page.
 */
export type EditorCanvas = FrameLocator | Page;
