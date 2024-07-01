/**
 * External dependencies
 */
import type { PropsWithChildren } from 'react';
import { useEntityRecord } from '@wordpress/core-data';
import { createElement, useRef, useState } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

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
	postType,
	productId,
	children,
}: PropsWithChildren< ValidationProviderProps > ) {
	const validatorsRef = useRef< Record< string, Validator< T > > >( {} );
	const fieldRefs = useRef< Record< string, HTMLElement > >( {} );
	const [ errors, setErrors ] = useState< {
		message?: ValidationErrors;
		context?: string;
	} >( {} );
	// const [ errors, setErrors ] = useState< ValidationErrors >( {} );
	const { record: initialValue } = useEntityRecord< T >(
		'postType',
		postType,
		productId
	);

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

	function unRegisterValidator( validatorId: string ): void {
		if ( validatorsRef.current[ validatorId ] ) {
			delete validatorsRef.current[ validatorId ];
		}
		if ( fieldRefs.current[ validatorId ] ) {
			delete fieldRefs.current[ validatorId ];
		}
	}

	async function validateField(
		validatorId: string,
		newData?: Partial< T >,
		errorContext = ''
	): ValidatorResponse {
		const validators = validatorsRef.current;
		if ( validatorId in validators ) {
			const validator = validators[ validatorId ];
			const result = validator( initialValue, newData );

			return result.then( ( error ) => {
				// console.log( 'errorContext', errorContext );
				// console.log( 'validatorId', validatorId );
				// console.log( 'error', error );
				// const aaaa = {
				// 	[ validatorId ]: { message: error, context: errorContext },
				// };
				// console.log( 'aaaa', aaaa );
				// setErrors( ( currentErrors ) => ( {
				// 	...currentErrors,
				// 	[ validatorId ]: { message: error, context: errorContext },
				// } ) );
				setErrors( ( currentErrors ) => ( {
					...currentErrors,
					[ validatorId ]: { validatorId, ...error },
				} ) );
				// return { message: error, context: errorContext };
				return error;
			} );
		}

		return Promise.resolve( undefined );
	}
	// console.log( '======> errors', errors );

	async function getFieldAndTabByValidatorId(
		_validatorId: string
	): Promise< ValidationErrors > {
		console.log( '======> errors', errors );
		// console.log( '======> fieldRefs', fieldRefs );
		// console.log( 'validatorId', validatorId );
		// console.log( '======>222222 errors', errors[ validatorId ] );
		// console.log( '======>333333 fieldRefs', fieldRefs.current[ validatorId ] );
		const newErrors: ValidationErrors = {};
		const validators = validatorsRef.current;
		for ( const validatorId in validators ) {
			newErrors[ validatorId ] = await validateField(
				validatorId,
				undefined
			);
		}

		console.log( 'getFieldAndTabByValidatorId', newErrors );
		// return {
		// 	fieldRef: fieldRefs.current[ validatorId ],
		// 	context: errors,
		// };
		// const field = fieldRefs.current[ validatorId ];
		// setTimeout( () => {
		// 	const props = block_id ? { tab, block_id } : { tab };
		// 	return getNewPath( props );
		// 	navigateTo( { url } );
		// }
		// , 1000 );
		// if ( field ) {
		// 	field.focus();
		// }
	}

	async function validateAll(
		newData: Partial< T >
	): Promise< ValidationErrors > {
		const newErrors: ValidationErrors = {};
		const validators = validatorsRef.current;
		console.log( 'newData', newData );

		for ( const validatorId in validators ) {
			newErrors[ validatorId ] = await validateField(
				validatorId,
				newData
			);
		}
		// console.log( 'errors', errors );
		// console.log( 'validators2', validators );
		console.log( 'newErrors2', newErrors );

		setErrors( newErrors );
		console.log( 'errors2', errors );

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
				getFieldAndTabByValidatorId,
				registerValidator,
				unRegisterValidator,
				validateField,
				validateAll,
			} }
		>
			{ children }
		</ValidationContext.Provider>
	);
}
