import { useRef, useEffect } from 'react';

/**
 * Use Previous from https://usehooks.com/usePrevious/.
 * @param {mixed} value
 */
export const usePrevious = ( value ) => {
	const ref = useRef();

	useEffect( () => {
		ref.current = value;
	}, [ value ] );

	return ref.current;
};
