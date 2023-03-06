const { customerBilling, customerShipping } = require( './customer' );
const { batch, getBatchPayloadExample } = require( './batch-update' );
const { errorResponse } = require( './error-response' );

module.exports = {
	customerBilling,
	customerShipping,
	batch,
	getBatchPayloadExample,
	errorResponse,
};
