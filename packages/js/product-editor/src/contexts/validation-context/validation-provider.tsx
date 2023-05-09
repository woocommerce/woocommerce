/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { PropsWithChildren } from 'react';

/**
 * Internal dependencies
 */
import {
	ValidationErrors,
	ValidationProviderProps,
	Validator,
	ValidatorResponse,
} from './types';
import { ValidationContext } from './validation-context';

export function ValidationProvider< T >( {
	initialValue,
	children,
}: PropsWithChildren< ValidationProviderProps< T > > ) {
	const [ validators, setValidators ] = useState<
		Record< string, Validator< T > >
	>( {} );
	const [ errors, setErrors ] = useState< ValidationErrors >( {} );

	function registerValidator(
		name: string,
		validator: Validator< T >
	): void {
		setValidators( ( currentValidators ) => ( {
			...currentValidators,
			[ name ]: validator,
		} ) );
	}

	async function validateField( name: string ): ValidatorResponse {
		if ( name in validators ) {
			const validator = validators[ name ];
			const result = validator( initialValue );

			return result.then( ( error ) => {
				setErrors( ( currentErrors ) => ( {
					...currentErrors,
					[ name ]: error,
				} ) );
				return error;
			} );
		}

		return Promise.resolve( undefined );
	}

	async function validateAll(): Promise< ValidationErrors > {
		const newErrors: ValidationErrors = {};

		for ( let name in validators ) {
			newErrors[ name ] = await validateField( name );
		}

		setErrors( newErrors );

		return newErrors;
	}

	return (
		<ValidationContext.Provider
			value={ {
				errors,
				validators,
				registerValidator,
				validateField,
				validateAll,
			} }
		>
			{ children }
		</ValidationContext.Provider>
	);
}
