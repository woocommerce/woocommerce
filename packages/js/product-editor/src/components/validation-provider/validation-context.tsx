/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ValidationContextProps } from './types';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const ValidationContext = createContext< ValidationContextProps >( {
	errors: {},
} );
