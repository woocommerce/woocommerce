/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ValidationContextProps } from './types';

export const ValidationContext = createContext< ValidationContextProps< any > >(
	{
		errors: {},
		registerValidator: () => {},
		validateField: () => Promise.resolve( undefined ),
		validateAll: () => Promise.resolve( {} ),
	}
);
