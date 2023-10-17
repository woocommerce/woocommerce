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
	deps: DependencyList = []
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

	return {
		ref,
		error: context.errors[ validatorId ],
		isValidating,
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		async validate( data: any ) {
			setIsValidating( true );
			return context.validateField( validatorId, data ).finally( () => {
				setIsValidating( false );
			} );
		},
	};
}
