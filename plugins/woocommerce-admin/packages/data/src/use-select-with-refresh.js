/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

const useInterval = ( callback, interval ) => {
	const savedCallback = useRef();
	useEffect( () => {
		savedCallback.current = callback;
	}, [ callback ] );
	useEffect( () => {
		const handler = ( ...args ) => savedCallback.current( ...args );
		if ( interval !== null ) {
			const id = setInterval( handler, interval );
			return () => clearInterval( id );
		}
	}, [ interval ] );
};

export const useSelectWithRefresh = (
	mapSelectToProps,
	invalidationCallback,
	interval,
	dependencies
) => {
	const result = useSelect( mapSelectToProps, dependencies );
	useInterval( invalidationCallback, interval );
	return result;
};
