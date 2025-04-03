/**
 * External dependencies
 */
import type { ComponentType } from 'react';
import { useMemo, createElement } from '@wordpress/element';
import { Template, TemplateArray } from '@wordpress/blocks';
import { Field, DataFormControlProps } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getProductField } from './fields';
import { ProductDataFormControlProps } from './fields/types';

/**
 * Get the property key for a field definition
 *
 * @param field - The field definition
 * @return The key for the field
 */
function getFieldKey( field: Template ): string {
	const attributes = field[ 1 ] || {};
	// We support the block binding structure.
	if (
		attributes.metadata?.bindings?.value?.source ===
			'woocommerce/entity-product' &&
		attributes.metadata?.bindings?.value?.args?.prop
	) {
		return attributes.metadata?.bindings?.value?.args?.prop;
	} else if ( attributes.property ) {
		return attributes.property;
	} else if ( attributes.name ) {
		return attributes.name;
	}
	return field[ 0 ];
}

type FieldGroup = {
	type: 'fields';
	content: Field< Product >[];
};

type ColumnGroup = {
	type: 'column';
	content: Template;
};

function addAttributesToEdit(
	Edit: string | ComponentType< ProductDataFormControlProps >,
	attributes: Record< string, unknown >
): string | ComponentType< DataFormControlProps< Product > > {
	if ( typeof Edit === 'string' ) {
		return Edit;
	}
	return function EditWithAttributes( props ) {
		return <Edit { ...props } attributes={ attributes } />;
	};
}
/**
 * Hook that transforms field definitions into DataForm compatible field objects,
 * grouping fields that appear before and after column blocks.
 *
 * @param fields - Array of field definitions
 * @return Array of grouped fields and columns
 */
export function useDataFormProductFields(
	fields: TemplateArray = []
): ( FieldGroup | ColumnGroup )[] {
	return useMemo( () => {
		const result: ( FieldGroup | ColumnGroup )[] = [];
		let currentFields: Field< Product >[] = [];

		const flushCurrentFields = () => {
			if ( currentFields.length > 0 ) {
				result.push( {
					type: 'fields',
					content: currentFields,
				} );
				currentFields = [];
			}
		};

		fields.forEach( ( field ) => {
			const [ fieldName, params ] = field;

			// If this is a columns block
			if ( fieldName === 'core/columns' ) {
				flushCurrentFields();
				result.push( {
					type: 'column',
					content: field,
				} );
			} else {
				// Convert regular field
				const fieldDefinition = getProductField( fieldName );
				const convertedField: Field< Product > = {
					...fieldDefinition,
					label: params?.label || fieldDefinition?.label,
					Edit:
						fieldDefinition?.Edit && params
							? addAttributesToEdit(
									fieldDefinition.Edit,
									params
							  )
							: fieldDefinition?.Edit,
					id: getFieldKey( [ fieldName, params ] ),
				};
				currentFields.push( convertedField );
			}
		} );

		// Don't forget remaining fields
		flushCurrentFields();

		return result;
	}, [ fields ] );
}
