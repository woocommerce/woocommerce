export interface RestApiErrorData {
	status?: number;
}

export type RestApiError = {
	code: string;
	data?: RestApiErrorData;
	message: string;
};
