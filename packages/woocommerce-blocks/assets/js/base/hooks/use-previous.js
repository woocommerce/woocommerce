/**
 * External dependencies
 */
import { useRef, useEffect } from 'react';

/**
 * Use Previous based on https://usehooks.com/usePrevious/.
 * @param {mixed}    value
 * @param {Function} [validation] Function that needs to validate for the value
 *                                to be updated.
 */
export const usePrevious = ( value, validation ) => {
	const ref = useRef();

	useEffect( () => {
		if (
			ref.current !== value &&
			( ! validation || validation( value, ref.current ) )
		) {
			ref.current = value;
		}
	}, [ value, ref.current ] );

	return ref.current;
};
