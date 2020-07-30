/**
 * Internal dependencies
 */
import { actions } from './reducer';

export const emitterCallback = ( type, dispatcher ) => (
	callback,
	priority = 10
) => {
	const action = actions.addEventCallback( type, callback, priority );
	dispatcher( action );
	return () => {
		dispatcher( actions.removeEventCallback( type, action.id ) );
	};
};
