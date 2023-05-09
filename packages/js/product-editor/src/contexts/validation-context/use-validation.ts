/**
 * External dependencies
 */
import { useContext, useMemo, useState } from '@wordpress/element';
import { DependencyList } from 'react';

/**
 * Internal dependencies
 */
import { Validator } from './types';
import { ValidationContext } from './validation-context';

export function useValidation< T >(
	name: string,
	validator: Validator< T >,
	deps: DependencyList = []
) {
	const context = useContext( ValidationContext );
	const [ isValidating, setIsValidating ] = useState( false );

	useMemo( () => context.registerValidator( name, validator ), deps );

	return {
		name,
		error: context.errors[ name ],
		isValidating,
		async validate() {
			setIsValidating( true );
			return context.validateField( name ).finally( () => {
				setIsValidating( false );
			} );
		},
	};
}
