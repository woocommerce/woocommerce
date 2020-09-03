import { AxiosResponse } from 'axios';
import { APIResponse, APIError } from '../api-service';
import { AxiosInterceptor } from './axios-interceptor';

export class AxiosResponseInterceptor extends AxiosInterceptor {
	/**
	 * Transforms the Axios response into our API response to be consumed in a consistent manner.
	 *
	 * @param {AxiosResponse} response The respons ethat we need to transform.
	 * @return {Promise} A promise containing the APIResponse.
	 */
	protected onResponseSuccess( response: AxiosResponse ): Promise<APIResponse> {
		return Promise.resolve<APIResponse>(
			new APIResponse( response.status, response.headers, response.data ),
		);
	}

	/**
	 * Transforms HTTP errors into an API error if the error came from the API.
	 *
	 * @param {*} error The error that was caught.
	 */
	protected onResponseRejected( error: any ): Promise<APIResponse> {
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
