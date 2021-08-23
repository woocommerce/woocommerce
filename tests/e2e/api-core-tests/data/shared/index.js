const { customerBilling, customerShipping } = require('./customer');
const {
	batchUpdatePayload,
	batchCreate,
	batchUpdate,
	batchDelete,
	getBatchPayloadExample
} = require('./batch-update');
const errorRessponse = require('./customer');

module.exports = {
	customerBilling,
	customerShipping,
	batchUpdatePayload,
	batchCreate,
	batchUpdate,
	batchDelete,
	getBatchPayloadExample,
	errorRessponse
}
