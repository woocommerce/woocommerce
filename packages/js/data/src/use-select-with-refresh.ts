/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

const useInterval = (
	callback: ( ...args: unknown[] ) => void,
	interval: Parameters< typeof setInterval >[ 1 ]
) => {
	const savedCallback = useRef< ( ...args: unknown[] ) => void >();
	useEffect( () => {
		savedCallback.current = callback;
	}, [ callback ] );
	useEffect( () => {
		const handler = ( ...args: unknown[] ) => {
			if ( savedCallback.current ) {
				savedCallback.current( ...args );
			}
		};
		if ( interval !== null ) {
			const id = setInterval( handler, interval );
			return () => clearInterval( id );
		}
	}, [ interval ] );
};

export const useSelectWithRefresh = (
	mapSelectToProps: Parameters< typeof useSelect >[ 0 ],
	invalidationCallback: Parameters< typeof useInterval >[ 0 ],
	interval: Parameters< typeof useInterval >[ 1 ],
	dependencies: Parameters< typeof useSelect >[ 1 ]
) => {
	const result = useSelect( mapSelectToProps, dependencies );
	useInterval( invalidationCallback, interval );
	return result;
};
