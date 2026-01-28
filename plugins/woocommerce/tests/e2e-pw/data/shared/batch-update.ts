/**
 * Shared model for batch updates for a resource.
 *
 * Note that by default the update endpoint is limited to 100 objects to be created, updated, or deleted.
 *
 * @param action    Batch action. Must be one of: create, update, or delete.
 * @param resources A list of resource objects. For the delete action, this will be a list of IDs.
 * @param payload   The batch payload object. Defaults to an empty object.
 */

export type BatchAction = 'create' | 'update' | 'delete';

export interface BatchPayload< T = unknown > {
	create?: T[];
	update?: T[];
	delete?: number[];
}

export const batch = < T >(
	action: BatchAction,
	resources: T[] | number[] = [],
	payload: BatchPayload< T > = {}
): BatchPayload< T > | undefined => {
	if ( ! [ 'create', 'update', 'delete' ].includes( action ) ) {
		return;
	}

	if ( resources.length === 0 ) {
		return;
	}

	if ( action === 'create' ) {
		payload.create = [ ...( resources as T[] ) ];
	}

	if ( action === 'update' ) {
		payload.update = [ ...( resources as T[] ) ];
	}

	if ( action === 'delete' ) {
		payload.delete = [ ...( resources as number[] ) ];
	}

	return payload;
};

export const getBatchPayloadExample = < T >(
	resource: T
): BatchPayload< T > | undefined => {
	let batchUpdatePayload: BatchPayload< T > | undefined = {};
	batchUpdatePayload = batch( 'create', [ resource ], batchUpdatePayload );
	batchUpdatePayload = batch(
		'update',
		[ resource ],
		batchUpdatePayload as BatchPayload< T >
	);
	batchUpdatePayload = batch(
		'delete',
		[ 1, 2, 3 ],
		batchUpdatePayload as BatchPayload< T >
	);
	return batchUpdatePayload;
};
