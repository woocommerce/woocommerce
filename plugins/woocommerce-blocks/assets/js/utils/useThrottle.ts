/* eslint-disable you-dont-need-lodash-underscore/throttle */

/**
 * External dependencies
 */
import { DebouncedFunc, throttle, ThrottleSettings } from 'lodash';
import { useCallback, useEffect, useRef } from 'react';

/**
 * Throttles a function inside a React functional component
 */
export function useThrottle< T extends ( ...args: unknown[] ) => unknown >(
	cb: T,
	delay: number,
	options?: ThrottleSettings
): DebouncedFunc< T > {
	const cbRef = useRef( cb );

	useEffect( () => {
		cbRef.current = cb;
	} );

	// Disabling because we can't pass an arrow function in this case
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const throttledCb = useCallback(
		throttle( ( ...args ) => cbRef.current( ...args ), delay, options ),
		[ delay ]
	);

	return throttledCb;
}
