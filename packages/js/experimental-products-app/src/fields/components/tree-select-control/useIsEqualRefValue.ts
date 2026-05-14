/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';

const isObject = ( value: unknown ): value is Record< string, unknown > => {
	return typeof value === 'object' && value !== null;
};

const isEqual = ( value: unknown, other: unknown ): boolean => {
	if ( Object.is( value, other ) ) {
		return true;
	}

	if ( Array.isArray( value ) || Array.isArray( other ) ) {
		return (
			Array.isArray( value ) &&
			Array.isArray( other ) &&
			value.length === other.length &&
			value.every( ( item, index ) => isEqual( item, other[ index ] ) )
		);
	}

	if ( isObject( value ) || isObject( other ) ) {
		if ( ! isObject( value ) || ! isObject( other ) ) {
			return false;
		}

		const keys = Object.keys( value );
		return (
			keys.length === Object.keys( other ).length &&
			keys.every( ( key ) => isEqual( value[ key ], other[ key ] ) )
		);
	}

	return false;
};

/**
 * Stores value in a ref. In subsequent render, value will be compared with ref.current.
 * If it is equal, returns ref.current; else, set ref.current to be value.
 *
 * This is useful for objects used in hook dependencies.
 *
 * @param value Value to be stored in ref.
 * @return Value stored in ref.
 */
const useIsEqualRefValue = < T >( value: T ): T => {
	const optionsRef = useRef< T >( value );

	if ( ! isEqual( optionsRef.current, value ) ) {
		optionsRef.current = value;
	}

	return optionsRef.current;
};

export default useIsEqualRefValue;
