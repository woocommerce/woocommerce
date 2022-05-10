type WPApiFetchError = {
	code: string;
	message: string;
};

type WPInternalServerError = {
	code: string;
	message: string;
	additional_errors: unknown[];
	data: {
		status: number;
	};
};

export type RestApiError = WPApiFetchError | WPInternalServerError;

export const isRestApiError = ( error: unknown ): error is RestApiError =>
	( error as RestApiError ).code !== undefined &&
	( error as RestApiError ).message !== undefined;
