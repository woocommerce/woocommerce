/**
 * A structured response from the API.
 */
export class APIResponse< T = any > {
	public readonly status: number;
	public readonly headers: any;
	public readonly data: T;

	public constructor( status: number, headers: any, data: T ) {
		this.status = status;
		this.headers = headers;
		this.data = data;
	}
}

/**
 * A structured error from the API.
 */
export class APIError {
	public readonly code: string;
	public readonly message: string;
	public readonly data: any;

	public constructor( code: string, message: string, data: any ) {
		this.code = code;
		this.message = message;
		this.data = data;
	}
}

/**
 * Checks whether or not an APIResponse contains an error.
 *
 * @param {APIResponse} response The response to evaluate.
 */
export function isAPIError( response: APIResponse ): response is APIResponse< APIError > {
	return response.status < 200 || response.status >= 400;
}

/**
 * An interface for implementing services to make calls against the API.
 */
export interface APIService {
	/**
	 * Performs a GET request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      params Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	get< T >(
		endpoint: string,
		params?: any
	): Promise< APIResponse< T >>;

	/**
	 * Performs a POST request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	post< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T >>;

	/**
	 * Performs a PUT request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	put< T >( endpoint: string, data?: any ): Promise< APIResponse< T >>;

	/**
	 * Performs a PATCH request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	patch< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T >>;

	/**
	 * Performs a DELETE request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	delete< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T >>;
}
