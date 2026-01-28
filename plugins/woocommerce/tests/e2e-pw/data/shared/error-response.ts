/**
 * API error response format
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#errors
 */

export interface ErrorResponse {
	code: string;
	message: string;
	data: {
		status: number;
	};
}

export const errorResponse: ErrorResponse = {
	code: '',
	message: '',
	data: {
		status: 400,
	},
};
