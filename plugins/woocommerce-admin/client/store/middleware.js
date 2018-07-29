/** @format */

export function applyMiddleware( store, middlewares ) {
	middlewares = middlewares.slice();
	middlewares.reverse();
	let dispatch = store.dispatch;
	middlewares.forEach( middleware => ( dispatch = middleware( store )( dispatch ) ) );
	return Object.assign( store, { dispatch } );
}

export const addThunks = ( { getState } ) => next => action => {
	if ( 'function' === typeof action ) {
		return action( getState );
	}
	return next( action );
};
