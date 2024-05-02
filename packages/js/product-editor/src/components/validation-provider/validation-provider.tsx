/**
 * External dependencies
 */
import { ErrorObject } from 'ajv';
import apiFetch from '@wordpress/api-fetch';
import { createElement, useEffect, useState } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { PropsWithChildren } from 'react';
import { useEntityProp, useEntityRecord } from '@wordpress/core-data';
import { useSelect, select as WPSelect } from '@wordpress/data';

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
	children,
}: PropsWithChildren< ValidationProviderProps > ) {
	const [ errors, setErrors ] = useState< { [ key: string ]: ErrorObject } >(
		{}
	);
	const [ schema, setSchema ] = useState< Schema | null >( null );
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);

	const productConfig: EntityConfig = useSelect(
		( select: typeof WPSelect ) => {
			const { getEntityConfig } = select( 'core' );
			return getEntityConfig( 'postType', 'product' );
		}
	);

	useEffect( () => {
		if ( ! productConfig?.baseURL ) {
			return;
		}
		apiFetch< OptionsResponse >( {
			path: productConfig.baseURL,
			method: 'OPTIONS',
		} ).then( ( results ) => {
			setSchema( results.schema );
		} );
	}, [ productConfig ] );

	const { editedRecord: product } = useEntityRecord< Product >(
		'postType',
		'product',
		productId
	);

	useEffect( () => {
		if ( ! schema || ! product ) {
			return;
		}

		const validate = validator.compile( schema );
		const valid = validate( product );
		const newErrors =
			! valid && validate.errors
				? getErrorDictionary( validate.errors, schema )
				: {};

		setErrors( newErrors );
	}, [ product, schema ] );

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
