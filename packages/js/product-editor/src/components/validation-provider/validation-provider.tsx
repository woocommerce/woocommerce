/**
 * External dependencies
 */
import { ErrorObject } from 'ajv';
import { createElement, useEffect, useState } from '@wordpress/element';
import { PropsWithChildren } from 'react';

/**
 * Internal dependencies
 */
import {
	EntityConfig,
	Schema,
	OptionsResponse,
	ValidationProviderProps,
} from './types';
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

	useEffect( () => {
		console.log('changing');
		console.log(record);
		console.log(schema);
		if ( ! schema || ! record ) {
			return;
		}

		const validate = validator.compile( schema );
		const valid = validate( record );
		const newErrors =
			! valid && validate.errors
				? getErrorDictionary( validate.errors, schema )
				: {};

		setErrors( newErrors );
	}, [ record, schema ] );

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
