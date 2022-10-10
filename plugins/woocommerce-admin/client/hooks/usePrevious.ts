/**
 * External dependencies
 */
import { useRef, useEffect } from 'react';

/**
 * Keep track of a previous value.
 * @param value Value to track.
 * @returns unknown
 */
export const usePrevious = ( value: unknown ) => {
	const ref = useRef();
	useEffect( () => {
		ref.current = value;
	}, [ value ] );
	return ref.current;
};
