const defaultState = {
	resources: {},
};

/**
 * Primary reducer for fresh-data apiclient data.
 *
 * @param {Object} state The base state for fresh-data.
 * @param {Object} action Action object to be processed.
 * @return {Object} The new state, or the previous state if this action doesn't belong to fresh-data.
 */
export default function reducer( state = defaultState, action ) {
	switch ( action.type ) {
		case 'FRESH_DATA_REQUESTED':
			return reduceRequested( state, action );
		case 'FRESH_DATA_RECEIVED':
			return reduceReceived( state, action );
		default:
			return state;
	}
}

export function reduceRequested( state, action ) {
	const newResources = action.resourceNames.reduce( ( resources, name ) => {
		resources[ name ] = { lastRequested: action.time };
		return resources;
	}, {} );
	return reduceResources( state, newResources );
}

export function reduceReceived( state, action ) {
	const newResources = Object.keys( action.resources ).reduce(
		( resources, name ) => {
			const resource = { ...action.resources[ name ] };
			if ( resource.data ) {
				resource.lastReceived = action.time;
			}
			if ( resource.error ) {
				resource.lastError = action.time;
			}
			resources[ name ] = resource;
			return resources;
		},
		{}
	);
	return reduceResources( state, newResources );
}

export function reduceResources( state, newResources ) {
	const updatedResources = Object.keys( newResources ).reduce(
		( resources, resourceName ) => {
			const resource = resources[ resourceName ];
			const newResource = newResources[ resourceName ];
			resources[ resourceName ] = { ...resource, ...newResource };
			return resources;
		},
		{ ...state.resources }
	);

	return { ...state, resources: updatedResources };
}
