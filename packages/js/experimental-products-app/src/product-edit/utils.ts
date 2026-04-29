/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';

export const EXCLUDED_PRODUCT_EDIT_FIELD_IDS = [
	'images_count',
	'price_summary',
	'inventory_summary',
	'organization_summary',
	'visibility_summary',
	'downloadable_count',
	'shipping_summary',
	'linked_products_count',
] as const;

const EXCLUDED_PRODUCT_EDIT_FIELD_ID_SET = new Set(
	EXCLUDED_PRODUCT_EDIT_FIELD_IDS
);

type ProductField = Field< ProductEntityRecord >;

function normalizeValue( value: unknown ) {
	if ( value === undefined ) {
		return '__undefined__';
	}

	return JSON.stringify( value );
}

function getMixedValueFallback( sample: unknown ) {
	if ( Array.isArray( sample ) ) {
		return [];
	}

	if ( sample === null ) {
		return null;
	}

	if ( typeof sample === 'string' ) {
		return '';
	}

	return undefined;
}

function getFieldValue( field: ProductField, item: ProductEntityRecord ) {
	if ( typeof field.getValue === 'function' ) {
		return field.getValue( {
			item,
		} );
	}

	return item[ field.id as keyof ProductEntityRecord ];
}

export function getProductEditFields( fields: ProductField[] ): ProductField[] {
	return fields.filter(
		( field ) =>
			! EXCLUDED_PRODUCT_EDIT_FIELD_ID_SET.has(
				field.id as ( typeof EXCLUDED_PRODUCT_EDIT_FIELD_IDS )[ number ]
			)
	);
}

export function buildMergedProductEditData(
	products: ProductEntityRecord[]
): ProductEntityRecord {
	if ( products.length === 0 ) {
		return {} as ProductEntityRecord;
	}

	const mergedData: Record< string, unknown > = {};
	const keys = Array.from(
		new Set( products.flatMap( ( product ) => Object.keys( product ) ) )
	);

	keys.forEach( ( key ) => {
		const values = products.map(
			( product ) => product[ key as keyof ProductEntityRecord ]
		);
		const firstDefinedValue = values.find(
			( value ) => value !== undefined
		);
		const areValuesEqual = values.every(
			( value ) =>
				normalizeValue( value ) === normalizeValue( values[ 0 ] )
		);

		mergedData[ key ] = areValuesEqual
			? values[ 0 ]
			: getMixedValueFallback( firstDefinedValue );
	} );

	return mergedData as ProductEntityRecord;
}

export function getMixedProductEditFieldIds(
	fields: ProductField[],
	products: ProductEntityRecord[]
) {
	if ( products.length <= 1 ) {
		return [];
	}

	return fields.reduce< string[] >( ( mixedFields, field ) => {
		const values = products.map( ( product ) =>
			getFieldValue( field, product )
		);
		const isMixed = values.some(
			( value ) =>
				normalizeValue( value ) !== normalizeValue( values[ 0 ] )
		);

		if ( isMixed ) {
			mixedFields.push( field.id );
		}

		return mixedFields;
	}, [] );
}

export function getVisibleProductEditFields(
	fields: ProductField[],
	data: ProductEntityRecord
) {
	return fields.filter( ( field ) => {
		if ( typeof field.isVisible !== 'function' ) {
			return true;
		}

		return field.isVisible( data );
	} );
}
