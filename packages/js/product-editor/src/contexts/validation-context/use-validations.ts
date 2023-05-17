/**
 * External dependencies
 */
import { useContext, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ValidationErrors } from './types';
import { ValidationContext } from './validation-context';

function isInvalid( errors: ValidationErrors ) {
	return Object.values( errors ).some( Boolean );
}

export function useValidations() {
	const context = useContext( ValidationContext );
	const [ isValidating, setIsValidating ] = useState( false );

	return {
		isValidating,
		async validate() {
			setIsValidating( true );
			return new Promise< void >( ( resolve, reject ) => {
				context
					.validateAll()
					.then( ( errors ) => {
						if ( isInvalid( errors ) ) {
							reject( errors );
						} else {
							resolve();
						}
					} )
					.catch( () => {
						reject( context.errors );
					} );
			} ).finally( () => {
				setIsValidating( false );
			} );
		},
	};
}
