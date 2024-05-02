/**
 * External dependencies
 */
import { ErrorObject, ValidateFunction } from 'ajv';
import { createElement, useEffect, useState } from '@wordpress/element';
import { PropsWithChildren, useCallback, useMemo } from 'react';

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

	useEffect( () => {
		if ( ! validate || ! record || ! schema ) {
			return;
		}

		const valid = validate( record );
		const newErrors =
			! valid && validate.errors
				? getErrorDictionary( validate.errors, schema )
				: {};

		setErrors( newErrors );
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
