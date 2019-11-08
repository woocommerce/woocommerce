/**
 * External dependencies
 */
import { useState, useEffect } from 'react';

/**
 * Debounce effects based on https://dev.to/gabe_ragland/debouncing-with-react-hooks-jci
 * @param {mixed} value
 * @param {number} delay
 */
export const useDebounce = ( value, delay = 500 ) => {
	// State and setters for debounced value
	const [ debouncedValue, setDebouncedValue ] = useState( value );

	useEffect( () => {
		// Set debouncedValue to value (passed in) after the specified delay
		const handler = setTimeout( () => {
			setDebouncedValue( value );
		}, delay );
		return () => {
			clearTimeout( handler );
		};
	}, [ value ] );

	return debouncedValue;
};
