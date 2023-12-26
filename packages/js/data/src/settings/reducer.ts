/**
 * External dependencies
 */
import { union } from 'lodash';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { getResourceName } from '../utils';
import { Actions } from './actions';
import { Settings, SettingsState } from './types';

const updateGroupDataInNewState = (
	newState: SettingsState,
	{
		group,
		groupIds,
		data,
		time,
		error,
	}: {
		group: string;
		groupIds: string[];
		data: Settings;
		time: Date;
		error: unknown;
	}
) => {
	groupIds.forEach( ( id ) => {
		newState[ getResourceName( group, id ) ] = {
			data: data[ id ],
			lastReceived: time,
			error,
		};
	} );
	return newState;
};

const reducer: Reducer< SettingsState, Actions > = ( state = {}, action ) => {
	const newState = {};
	switch ( action.type ) {
		case TYPES.SET_IS_REQUESTING:
			state = {
				...state,
				[ action.group ]: {
					...state[ action.group ],
					isRequesting: action.isRequesting,
				},
			};
			break;
		case TYPES.CLEAR_IS_DIRTY:
			state = {
				...state,
				[ action.group ]: {
					...state[ action.group ],
					dirty: [],
				},
			};
			break;
		case TYPES.UPDATE_SETTINGS_FOR_GROUP:
		case TYPES.UPDATE_ERROR_FOR_GROUP:
			const { data, group, time } = action;
			const groupIds = data ? Object.keys( data ) : [];
			const error =
				action.type === TYPES.UPDATE_ERROR_FOR_GROUP
					? action.error
					: null;
			if ( data === null ) {
				state = {
					...state,
					[ group ]: {
						data: state[ group ] ? state[ group ].data : [],
						error,
						lastReceived: time,
					},
				};
			} else {
				const stateGroup = state[ group ];
				state = {
					...state,
					[ group ]: {
						data:
							stateGroup &&
							stateGroup.data &&
							Array.isArray( stateGroup.data )
								? [ ...stateGroup.data, ...groupIds ]
								: groupIds,
						error,
						lastReceived: time,
						isRequesting: state[ group ]?.isRequesting || false,
						dirty:
							state[ group ] && state[ group ].dirty
								? union( state[ group ].dirty, groupIds )
								: groupIds,
					},
					...updateGroupDataInNewState( newState, {
						group,
						groupIds,
						data,
						time,
						error,
					} ),
				};
			}
			break;
		case TYPES.CLEAR_SETTINGS:
			state = {};
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
