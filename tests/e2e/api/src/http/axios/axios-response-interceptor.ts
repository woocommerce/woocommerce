import { AxiosResponse } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';
import { HTTPResponse } from '../http-client';

/**
 * An interceptor for transforming the responses from axios into a consistent format for package consumers.
 */
export class AxiosResponseInterceptor extends AxiosInterceptor {
	/**
	 * Transforms the Axios response into our HTTP response.
	 *
	 * @param {AxiosResponse} response The response that we need to transform.
	 * @return {HTTPResponse} The HTTP response.
	 */
	protected onResponseSuccess( response: AxiosResponse ): HTTPResponse {
		return new HTTPResponse( response.status, response.headers, response.data );
	}

	/**
	 * Axios throws HTTP errors so we need to eat those errors and pass them normally.
	 *
	 * @param {*} error The error that was caught.
	 */
	protected onResponseRejected( error: any ): never {
		// Convert HTTP response errors into a form that we can handle them with.
		if ( error.response ) {
			throw new HTTPResponse( error.response.status, error.response.headers, error.response.data );
		}

		throw error;
	}
}
