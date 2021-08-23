/**
 * Shared model for batch updates for a resource.
 *
 * Note that by default the update is limited to 100 objects to be created, updated, or deleted.
 */
const batchUpdatePayload = {
	create: [],
	update: [],
	delete: [],
}

/**
 * Batch create a resource.
 *
 * @param {Array} resourcesToCreate A list of resource objects to create.
 * @returns
 */
const batchCreate = ( resourcesToCreate = [] ) => {
	if ( resourcesToCreate.length === 0 ) {
		return;
	}

	// Build array of resources to create
	const createArray = [];
	resourcesToCreate.forEach( ( resource ) => {
		createArray.push( resource );
	});

	batchUpdatePayload.create = createArray

	return createArray;
}

/**
 * Batch update resources.
 *
 * @param {Array} resourcesToUpdate A list of resource objects to update.
 * @returns
 */
const batchUpdate = ( resourcesToUpdate = [] ) => {
	if ( resourcesToUpdate.length === 0 ) {
		return
	}

	// Build array of resources to update
	const updateArray = [];
	resourcesToUpdate.forEach( ( resource ) => {
		updateArray.push( resource );
	});

	return updateArray;
}

/**
 * Batch delete resources.
 *
 * @param {Array} resourceIds A list of IDs of resource objects to delete.
 * @returns
 */
const batchDelete = ( resourceIds = [] ) => {
	if ( resourceIds.length === 0 ) {
		return;
	}

	// Build array of resources to delete
	const deleteArray = [];
	resourceIds.forEach( ( id ) => {
		deleteArray.push( id );
	});

	return deleteArray;
}

const getBatchPayloadExample = ( resource ) => {
	batchUpdatePayload.create = batchCreate( [ resource ] );
	batchUpdatePayload.update = batchUpdate( [ resource ] );
	batchUpdatePayload.delete = batchDelete( [ 1, 2, 3 ] );
	return batchUpdatePayload;
}

module.exports = {
	batchUpdatePayload,
	batchCreate,
	batchUpdate,
	batchDelete,
	getBatchPayloadExample
}
