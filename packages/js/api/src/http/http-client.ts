/**
 * A structured response from the HTTP client.
 */
export class HTTPResponse< T = any > {
	/**
	 * The status code from the response.
	 *
	 * @type {number}
	 */
	public readonly statusCode: number;

	/**
	 * The headers from the response.
	 *
	 * @type {Object.<string,string|string[]>}
	 */
	public readonly headers: any;

	/**
	 * The data from the response.
	 *
	 * @type {Object}
	 */
	public readonly data: T;

	/**
	 * Creates a new HTTP response instance.
	 *
	 * @param {number} statusCode The status code from the HTTP response.
	 * @param {Object.<string,string|string[]>} headers The headers from the HTTP response.
	 * @param {Object} data The data from the HTTP response.
	 */
	public constructor( statusCode: number, headers: any, data: T ) {
		this.statusCode = statusCode;
		this.headers = headers;
		this.data = data;
	}
}

/**
 * An interface for clients that make HTTP requests.
 */
export interface HTTPClient {
	/**
	 * Performs a GET request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*} params Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	get< T = any >( path: string, params?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a POST request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	post< T = any >( path: string, data?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a PUT request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	put< T = any >( path: string, data?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a PATCH request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	patch< T = any >( path: string, data?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a DELETE request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	delete< T = any >( path: string, data?: any ): Promise< HTTPResponse< T > >;
}
