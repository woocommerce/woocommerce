/**
 * API error response format
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#errors
 */
const errorResponse = {
	code: '',
	message: '',
	data: {
		status: 400,
	},
};

module.exports = { errorResponse };
