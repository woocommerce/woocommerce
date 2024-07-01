/**
 * External dependencies
 */
import { useContext, useMemo, useState, useEffect } from '@wordpress/element';
import { DependencyList } from 'react';

/**
 * Internal dependencies
 */
import { Validator } from './types';
import { ValidationContext } from './validation-context';

export function useValidation< T >(
	validatorId: string,
	validator: Validator< T >,
	deps: DependencyList = [],
	errorContext?: string
) {
	const context = useContext( ValidationContext );
	const [ isValidating, setIsValidating ] = useState( false );

	const ref = useMemo(
		() => context.registerValidator( validatorId, validator ),
		[ validatorId, ...deps ]
	);

	useEffect( () => {
		return () => {
			context.unRegisterValidator( validatorId );
		};
	}, [] );

	// console.log( '-------->>>>>>2 errorContext', errorContext );
	// console.log( 'context.errors[ validatorId ]', context.errors[ validatorId ] );

	return {
		ref,
		error: context.errors[ validatorId ]?.message,
		isValidating,
		async validate( newData?: Record< string, unknown > ) {
			setIsValidating( true );
			return context
				.validateField( validatorId, newData, errorContext )
				.finally( () => {
					setIsValidating( false );
				} );
		},
	};
}
