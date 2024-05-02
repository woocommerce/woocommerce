/**
 * External dependencies
 */
import Ajv from 'ajv';
import apiFetch from '@wordpress/api-fetch';
import { createElement, useEffect, useState } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { useEntityProp, useEntityRecord } from '@wordpress/core-data';
import { useSelect, select as WPSelect } from '@wordpress/data';
import { get } from 'lodash';

const ajv = new Ajv( { strict: false, allErrors: true } );

type EntityConfig = {
	baseURL: string;
};

type SchemaProperties = {
	type: string;
};

type Schema = {
	$schema: string;
	properties: SchemaProperties[];
};

type OptionsResponse = {
	schema: Schema;
};

export function ErrorList() {
	const [ errors, setErrors ] = useState< string[] >( [] );
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
		console.log( schema );
		// console.log( 'changing' );
		console.log( product );

		const validate = ajv.compile( schema );
		const valid = validate( product );
		if ( ! valid && validate.errors ) {
			console.log( validate.errors );
			const errorMessages = validate.errors.map( ( error ) => {
				const path = error.schemaPath.replace( '#/', '' ).split( '/' );
				path.pop();
				const property = get( schema, path );

				if (
					property.errorMessage &&
					property.errorMessage[ error.keyword ]
				) {
					return property.errorMessage[ error.keyword ] as string;
				}

				if ( typeof property.errorMessage === 'string' ) {
					return property.errorMessage as string;
				}

				return ( error.instancePath.replace( '/', '' ) +
					' ' +
					error.message ) as string;
			} );
			setErrors( errorMessages );
		}
	}, [ product ] );

	return (
		<div>
			{ errors.map( ( error, index ) => (
				<div key={ index }>{ error }</div>
			) ) }
		</div>
	);
}
