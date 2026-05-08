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
type ProductEditFieldId = ( typeof PRODUCT_EDIT_FIELD_IDS )[ number ];
type ProductVariationEntityRecord = ProductEntityRecord & {
	parent_id: number;
};

const PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'short_description',
	'description',
	'images',
	'images_count',
	'product_status',
	'sku',
	'price',
	'regular_price',
	'on_sale',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'price_summary',
	'stock',
	'stock_quantity',
	'manage_stock',
	'inventory_summary',
	'categories',
	'tags',
	'organization_summary',
	'type',
	'featured',
	'catalog_visibility',
	'visibility_summary',
	'downloadable',
	'downloadable_count',
	'external_url',
	'button_text',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'shipping_summary',
	'tax_status',
	'upsell_ids',
	'cross_sell_ids',
	'linked_products_count',
] as const;

const COMMON_PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'short_description',
	'description',
	'images',
	'product_status',
	'sku',
	'categories',
	'tags',
	'featured',
	'catalog_visibility',
	'upsell_ids',
] satisfies ProductEditFieldId[];

const SIMPLE_PRODUCT_EDIT_FIELD_IDS = [
	...COMMON_PRODUCT_EDIT_FIELD_IDS,
	'price',
	'regular_price',
	'on_sale',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'stock',
	'stock_quantity',
	'manage_stock',
	'downloadable',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'tax_status',
	'cross_sell_ids',
] satisfies ProductEditFieldId[];

const VARIABLE_PRODUCT_EDIT_FIELD_IDS = [
	...COMMON_PRODUCT_EDIT_FIELD_IDS,
	'stock',
	'stock_quantity',
	'manage_stock',
	'tax_status',
	'cross_sell_ids',
] satisfies ProductEditFieldId[];

const EXTERNAL_PRODUCT_EDIT_FIELD_IDS = [
	...COMMON_PRODUCT_EDIT_FIELD_IDS,
	'price',
	'regular_price',
	'on_sale',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'external_url',
	'button_text',
	'tax_status',
] satisfies ProductEditFieldId[];

const GROUPED_PRODUCT_EDIT_FIELD_IDS = [
	...COMMON_PRODUCT_EDIT_FIELD_IDS,
] as const;

const PRODUCT_TYPE_COMPATIBLE_FIELD_IDS = {
	simple: SIMPLE_PRODUCT_EDIT_FIELD_IDS,
	variable: VARIABLE_PRODUCT_EDIT_FIELD_IDS,
	grouped: GROUPED_PRODUCT_EDIT_FIELD_IDS,
	external: EXTERNAL_PRODUCT_EDIT_FIELD_IDS,
} satisfies Record< string, readonly ProductEditFieldId[] >;

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

function getProductTypeCompatibleFieldIds( product: ProductEntityRecord ) {
	const productType = product.type;

	if ( productType && productType in PRODUCT_TYPE_COMPATIBLE_FIELD_IDS ) {
		return PRODUCT_TYPE_COMPATIBLE_FIELD_IDS[
			productType as keyof typeof PRODUCT_TYPE_COMPATIBLE_FIELD_IDS
		];
	}

	return COMMON_PRODUCT_EDIT_FIELD_IDS;
}

export function isProductVariation(
	product: ProductEntityRecord
): product is ProductVariationEntityRecord {
	return product.type === 'variation' || Boolean( product.parent_id );
}

export function getProductVariationUpdatePath(
	product: ProductVariationEntityRecord
) {
	if ( ! product.parent_id ) {
		throw new Error(
			'Variation parent ID is required to update a variation.'
		);
	}

	return `/wc/v3/products/${ product.parent_id }/variations/${ product.id }`;
}

export function getProductWithUpdatedVariation(
	product: ProductEntityRecord,
	variation: ProductEntityRecord
): ProductEntityRecord {
	const embeddedVariations = product._embedded?.variations ?? [];
	const hasEmbeddedVariation = embeddedVariations.some(
		( embeddedVariation ) => embeddedVariation.id === variation.id
	);

	return {
		...product,
		_embedded: {
			...product._embedded,
			variations: hasEmbeddedVariation
				? embeddedVariations.map( ( embeddedVariation ) =>
						embeddedVariation.id === variation.id
							? variation
							: embeddedVariation
				  )
				: [ ...embeddedVariations, variation ],
		},
	};
}

export function findProductInList(
	products: ProductEntityRecord[],
	productId: number
) {
	for ( const product of products ) {
		if ( product.id === productId ) {
			return product;
		}

		const variation = product._embedded?.variations?.find(
			( embeddedVariation ) => embeddedVariation.id === productId
		);

		if ( variation ) {
			return variation;
		}
	}
}

function getCommonProductTypeCompatibleFieldIds(
	products: ProductEntityRecord[]
) {
	if ( products.length === 0 ) {
		return new Set< string >();
	}

	const [ firstProduct, ...remainingProducts ] = products;
	const commonFieldIds = new Set(
		getProductTypeCompatibleFieldIds( firstProduct )
	);

	remainingProducts.forEach( ( product ) => {
		const compatibleFieldIds = new Set(
			getProductTypeCompatibleFieldIds( product )
		);

		commonFieldIds.forEach( ( fieldId ) => {
			if ( ! compatibleFieldIds.has( fieldId ) ) {
				commonFieldIds.delete( fieldId );
			}
		} );
	} );

	return commonFieldIds;
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
	products: ProductEntityRecord[]
) {
	const compatibleFieldIds =
		getCommonProductTypeCompatibleFieldIds( products );

	return fields.reduce< ProductField[] >( ( visibleFields, field ) => {
		if ( ! compatibleFieldIds.has( field.id ) ) {
			return visibleFields;
		}

		const { isVisible } = field;

		if ( typeof isVisible !== 'function' ) {
			visibleFields.push( field );
			return visibleFields;
		}

		if ( products.every( ( product ) => isVisible( product ) ) ) {
			visibleFields.push( {
				...field,
				isVisible: undefined,
			} );
		}

		return visibleFields;
	}, [] );
}
