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

	function focusByValidatiorId( validatorId: string ) {
		const field = context.getFieldAndTabByValidatorId( validatorId );
		console.log( '----> context', context );
		// console.log( 'field', field );
		// if ( element ) {
		// 	element.focus();
		// }
	}

	return {
		isValidating,
		async validate( newData?: Partial< T > ) {
			setIsValidating( true );
			return new Promise< void >( ( resolve, reject ) => {
				context
					.validateAll( newData )
					.then( ( errors ) => {
						console.log( '---> errors', errors );
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
				console.log( '----> context', context );
				setIsValidating( false );
			} );
		},
		focusByValidatiorId,
	};
}
