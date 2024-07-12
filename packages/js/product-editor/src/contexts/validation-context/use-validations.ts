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

export function useValidations< T = unknown >() {
	const context = useContext( ValidationContext );
	const [ isValidating, setIsValidating ] = useState( false );

	async function focusByValidatorId( validatorId: string ) {
		const field = await context.getFieldByValidatorId( validatorId );
		if ( field ) {
			field.focus();
		}
	}

	return {
		isValidating,
		async validate( newData?: Partial< T > ) {
			setIsValidating( true );
			return new Promise< void >( ( resolve, reject ) => {
				context
					.validateAll( newData )
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
		focusByValidatorId,
	};
}
