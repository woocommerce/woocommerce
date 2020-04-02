export const errorTypes = {
	INVALID_EMAIL: 'email_invalid',
	INVALID_REQUEST: 'invalid_request_error',
	API_CONNECTION: 'api_connection_error',
	API_ERROR: 'api_error',
	AUTHENTICATION_ERROR: 'authentication_error',
	RATE_LIMIT_ERROR: 'rate_limit_error',
	CARD_ERROR: 'card_error',
	VALIDATION_ERROR: 'validation_error',
};

export const errorCodes = {
	INVALID_NUMBER: 'invalid_number',
	INVALID_EXPIRY_MONTH: 'invalid_expiry_month',
	INVALID_EXPIRY_YEAR: 'invalid_expiry_year',
	INVALID_CVC: 'invalid_cvc',
	INCORRECT_NUMBER: 'incorrect_number',
	INCOMPLETE_NUMBER: 'incomplete_number',
	INCOMPLETE_CVC: 'incomplete_cvc',
	INCOMPLETE_EXPIRY: 'incomplete_expiry',
	EXPIRED_CARD: 'expired_card',
	INCORRECT_CVC: 'incorrect_cvc',
	INCORRECT_ZIP: 'incorrect_zip',
	INVALID_EXPIRY_YEAR_PAST: 'invalid_expiry_year_past',
	CARD_DECLINED: 'card_declined',
	MISSING: 'missing',
	PROCESSING_ERROR: 'processing_error',
};
