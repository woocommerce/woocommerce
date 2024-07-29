/**
 * Shared model for batch updates for a resource.
 *
 * Note that by default the update endpoint is limited to 100 objects to be created, updated, or deleted.
 *
 * @param {string} action    Batch action. Must be one of: create, update, or delete.
 * @param {Array}  resources A list of resource objects. For the delete action, this will be a list of IDs.
 * @param {Object} payload   The batch payload object. Defaults to an empty object.
 */
const batch = ( action, resources = [], payload = {} ) => {
	if ( ! [ 'create', 'update', 'delete' ].includes( action ) ) {
		return;
	}

	if ( resources.length === 0 ) {
		return;
	}

	if ( action === 'create' ) {
		payload.create = [ ...resources ];
	}

	if ( action === 'update' ) {
		payload.update = [ ...resources ];
	}

	if ( action === 'delete' ) {
		payload.delete = [ ...resources ];
	}

	return payload;
};

const getBatchPayloadExample = ( resource ) => {
	let batchUpdatePayload = {};
	batchUpdatePayload = batch( 'create', [ resource ], batchUpdatePayload );
	batchUpdatePayload = batch( 'update', [ resource ], batchUpdatePayload );
	batchUpdatePayload = batch( 'delete', [ 1, 2, 3 ], batchUpdatePayload );
	return batchUpdatePayload;
};

module.exports = {
	batch,
	getBatchPayloadExample,
};
