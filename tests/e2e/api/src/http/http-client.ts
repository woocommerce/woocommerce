/**
 * A structured response from the HTTP client.
 */
export class HTTPResponse< T = any > {
	public readonly statusCode: number;
	public readonly headers: any;
	public readonly data: T;

	/**
	 * Creates a new HTTP response instance.
	 *
	 * @param {number} statusCode The status code from the HTTP response.
	 * @param {*}      headers The headers from the HTTP response.
	 * @param {*}      data The data from the HTTP response.
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
	 * @param {*}      params Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	get< T >( path: string, params?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a POST request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	post< T >( path: string, data?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a PUT request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	put< T >( path: string, data?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a PATCH request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	patch< T >( path: string, data?: any ): Promise< HTTPResponse< T > >;

	/**
	 * Performs a DELETE request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	delete< T >( path: string, data?: any ): Promise< HTTPResponse< T > >;
}
