/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FormContextType } from './types';

export const FormContext = createContext( {} as FormContextType );

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function useFormContext< Values extends Record< string, any > >() {
	const formContext = useContext< FormContextType< Values > >( FormContext );

	return formContext;
}
