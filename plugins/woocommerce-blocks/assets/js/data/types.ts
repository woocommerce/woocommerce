export interface ResponseError {
	code: string;
	message: string;
	data: {
		status: number;
		[ key: string ]: unknown;
	};
}
