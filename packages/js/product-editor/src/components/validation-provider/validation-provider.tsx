/**
 * External dependencies
 */
import { createElement, useEffect, useState } from '@wordpress/element';
import { ErrorObject, ValidateFunction } from 'ajv';
import { isEqual } from 'lodash';
import { PropsWithChildren, useMemo } from 'react';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { ValidationProviderProps } from './types';
import { ValidationContext } from './validation-context';
import { getErrorDictionary } from './get-error-dictionary';
import { validator } from './validator';

export function ValidationProvider( {
	schema,
	record,
	children,
}: PropsWithChildren< ValidationProviderProps > ) {
	const [ errors, setErrors ] = useState< { [ key: string ]: ErrorObject } >(
		{}
	);

	const validate = useMemo< ValidateFunction | null >( () => {
		if ( ! schema ) {
			return null;
		}
		return validator.compile( schema );
	}, [ schema ] );

	const debouncedValidate = useDebounce( () => {
		if ( ! validate || ! record || ! schema ) {
			return;
		}

		const valid = validate( record );
		const newErrors =
			! valid && validate.errors
				? getErrorDictionary( validate.errors, schema )
				: {};

		if ( isEqual( errors, newErrors ) ) {
			return;
		}

		setErrors( newErrors );
	}, 250 );

	useEffect( () => {
		debouncedValidate();
	}, [ record, validate, schema ] );

	return (
		<ValidationContext.Provider
			value={ {
				errors,
			} }
		>
			{ children }
		</ValidationContext.Provider>
	);
}
