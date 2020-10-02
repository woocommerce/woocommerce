import { AxiosResponse } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';
import { HTTPResponse } from '../http-client';

export class AxiosResponseInterceptor extends AxiosInterceptor {
	/**
	 * Transforms the Axios response into our HTTP response.
	 *
	 * @param {AxiosResponse} response The response that we need to transform.
	 * @return {Promise} A promise containing the HTTPResponse.
	 */
	protected onResponseSuccess( response: AxiosResponse ): Promise< HTTPResponse > {
		return Promise.resolve< HTTPResponse >(
			new HTTPResponse( response.status, response.headers, response.data ),
		);
	}

	/**
	 * Axios throws HTTP errors so we need to eat those errors and pass them normally.
	 *
	 * @param {*} error The error that was caught.
	 * @return {Promise} A promise containing the HTTPResponse.
	 */
	protected onResponseRejected( error: any ): Promise< any > {
		// Convert HTTP response errors into a form that we can handle them with.
		if ( error.response ) {
			return Promise.reject( new HTTPResponse( error.response.status, error.response.headers, error.response.data ) );
		}

		return Promise.reject( error );
	}
}
