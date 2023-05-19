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

	return {
		ref,
		error: context.errors[ validatorId ],
		isValidating,
		async validate() {
			setIsValidating( true );
			return context.validateField( validatorId ).finally( () => {
				setIsValidating( false );
			} );
		},
	};
}
