import { AxiosInstance, AxiosResponse } from 'axios';
import { APIError, APIResponse } from '../api-service';

export class APIResponseInterceptor {
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
				( error: any ) => this.onRejected( error )
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
	private onFulfilled( response: AxiosResponse ): Promise< APIResponse > {
		return Promise.resolve< APIResponse >(
			new APIResponse( response.status, response.headers, response.data )
		);
	}

	/**
	 * Transforms the Axios error into our API error to be consumed in a consistent manner.
	 *
	 * @param {*} error The error
	 * @return {Promise} A promise containing the APIError.
	 */
	private onRejected( error: any ): Promise< APIError > {
		let apiResponse: APIResponse | null = null;
		let message: string | null = null;
		if ( error.response ) {
			apiResponse = new APIResponse(
				error.response.status,
				error.response.headers,
				error.response.data
			);
		} else if ( error.request ) {
			message = 'No response was received from the server.';
		} else {
			// Keep bubbling up the errors that we aren't handling.
			throw error;
		}

		return Promise.reject( new APIError( apiResponse, message ) );
	}
}
