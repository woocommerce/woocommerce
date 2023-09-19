/**
 * External dependencies
 */
import { createElement, useRef, useState } from '@wordpress/element';
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
import { findFirstInvalidElement } from './helpers';

export function ValidationProvider< T >( {
	initialValue,
	children,
}: PropsWithChildren< ValidationProviderProps< T > > ) {
	const validatorsRef = useRef< Record< string, Validator< T > > >( {} );
	const fieldRefs = useRef< Record< string, HTMLElement > >( {} );
	const [ errors, setErrors ] = useState< ValidationErrors >( {} );

	function registerValidator(
		validatorId: string,
		validator: Validator< T >
	): React.Ref< HTMLElement > {
		validatorsRef.current = {
			...validatorsRef.current,
			[ validatorId ]: validator,
		};

		return ( element: HTMLElement ) => {
			fieldRefs.current[ validatorId ] = element;
		};
	}

	async function validateField(
		validatorId: string,
		newData?: Partial< T >
	): ValidatorResponse {
		const validators = validatorsRef.current;
		if ( validatorId in validators ) {
			const validator = validators[ validatorId ];
			const result = validator( initialValue, newData );

			return result.then( ( error ) => {
				setErrors( ( currentErrors ) => ( {
					...currentErrors,
					[ validatorId ]: error,
				} ) );
				return error;
			} );
		}

		return Promise.resolve( undefined );
	}

	async function validateAll(
		newData: Partial< T >
	): Promise< ValidationErrors > {
		const newErrors: ValidationErrors = {};
		const validators = validatorsRef.current;

		for ( const validatorId in validators ) {
			newErrors[ validatorId ] = await validateField(
				validatorId,
				newData
			);
		}

		setErrors( newErrors );

		const firstElementWithError = findFirstInvalidElement(
			fieldRefs.current,
			newErrors
		);

		firstElementWithError?.focus();

		return newErrors;
	}

	return (
		<ValidationContext.Provider
			value={ {
				errors,
				registerValidator,
				validateField,
				validateAll,
			} }
		>
			{ children }
		</ValidationContext.Provider>
	);
}
