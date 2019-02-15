/** @format */

const DEFAULT_STATE = [];

const notices = ( state = DEFAULT_STATE, action ) => {
	if ( action.type === 'ADD_NOTICE' ) {
		return [ ...state, action.notice ];
	}

	return state;
};

export default {
	notices,
};
