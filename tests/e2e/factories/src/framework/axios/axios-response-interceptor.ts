import { AxiosInstance, AxiosResponse } from 'axios';
import { APIError, APIResponse } from '../api-service';

export class AxiosResponseInterceptor {
	private readonly client: AxiosInstance;
	private interceptorID: number | null;

	public constructor( client: AxiosInstance ) {
		this.client = client;
		this.interceptorID = null;
	}

	/**
	 * Starts transforming the response and errors into a consistent format.
	 */
	public start(): void {
		if ( null === this.interceptorID ) {
			this.interceptorID = this.client.interceptors.response.use(
				// @ts-ignore: We WANT to change the type of response returned.
				( response ) => this.onFulfilled( response ),
				( error: any ) => AxiosResponseInterceptor.onRejected( error ),
			);
		}
	}

	/**
	 * Stops transforming the response and errors into a consistent format.
	 */
	public stop(): void {
		if ( null !== this.interceptorID ) {
			this.client.interceptors.response.eject( this.interceptorID );
			this.interceptorID = null;
		}
	}

	/**
	 * Transforms the Axios response into our API response to be consumed in a consistent manner.
	 *
	 * @param {AxiosResponse} response The respons ethat we need to transform.
	 * @return {Promise} A promise containing the APIResponse.
	 */
	private onFulfilled( response: AxiosResponse ): Promise<APIResponse> {
		return Promise.resolve<APIResponse>(
			new APIResponse( response.status, response.headers, response.data ),
		);
	}

	/**
	 * Transforms HTTP errors into an API error if the error came from the API.
	 *
	 * @param {*} error The error that was caught.
	 */
	private static onRejected( error: any ): Promise<APIResponse> {
		// Only transform API errors.
		if ( ! error.response ) {
			throw error;
		}

		throw new APIResponse(
			error.response.status,
			error.response.headers,
			new APIError(
				error.response.data.code,
				error.response.data.message,
				error.response.data.data,
			),
		);
	}
}
