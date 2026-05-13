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
	'brands',
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

const SIMPLE_PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'product_status',
	'catalog_visibility',
	'regular_price',
	'on_sale',
	'sale_price',
	'images',
	'downloadable',
	'sku',
	'stock',
	'manage_stock',
	'stock_quantity',
	'categories',
	'brands',
	'tags',
] satisfies ProductEditFieldId[];

const VARIABLE_PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'short_description',
	'description',
	'images',
	'product_status',
	'sku',
	'stock',
	'stock_quantity',
	'manage_stock',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'tax_status',
	'categories',
	'tags',
	'featured',
	'catalog_visibility',
	'upsell_ids',
	'cross_sell_ids',
] satisfies ProductEditFieldId[];

const EXTERNAL_PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'product_status',
	'catalog_visibility',
	'regular_price',
	'on_sale',
	'sale_price',
	'images',
	'external_url',
	'button_text',
	'sku',
	'categories',
	'brands',
	'tags',
	'featured',
] satisfies ProductEditFieldId[];

const GROUPED_PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'product_status',
	'catalog_visibility',
	'upsell_ids',
	'images',
	'sku',
	'categories',
	'brands',
	'tags',
	'featured',
] satisfies ProductEditFieldId[];

const PRODUCT_TYPE_COMPATIBLE_FIELD_IDS = {
	simple: SIMPLE_PRODUCT_EDIT_FIELD_IDS,
	variable: VARIABLE_PRODUCT_EDIT_FIELD_IDS,
	grouped: GROUPED_PRODUCT_EDIT_FIELD_IDS,
	external: EXTERNAL_PRODUCT_EDIT_FIELD_IDS,
} satisfies Record<
	'simple' | 'variable' | 'grouped' | 'external',
	readonly ProductEditFieldId[]
>;

const PARENT_OWNED_PRODUCT_EDIT_FIELD_ID_SET = new Set< ProductEditFieldId >( [
	'name',
	'short_description',
	'description',
	'product_status',
	'catalog_visibility',
	'categories',
	'brands',
	'tags',
	'type',
	'featured',
	'upsell_ids',
	'cross_sell_ids',
	'external_url',
	'button_text',
] );

const SELLABLE_PRODUCT_EDIT_FIELD_ID_SET = new Set< ProductEditFieldId >( [
	'price',
	'regular_price',
	'on_sale',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
] );

const BULK_UNSUPPORTED_PRODUCT_EDIT_FIELD_ID_SET =
	new Set< ProductEditFieldId >( [ 'sku' ] );

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

function isVariableProductParent( product: ProductEntityRecord ) {
	return product.type === 'variable' && ! product.parent_id;
}

export function isProductVariation(
	product: ProductEntityRecord
): product is ProductVariationEntityRecord {
	return product.type === 'variation' || Boolean( product.parent_id );
}

function getProductTypeCompatibleFieldIds(
	product: ProductEntityRecord
): readonly ProductEditFieldId[] {
	const productType =
		product.type === 'variable' ||
		product.type === 'grouped' ||
		product.type === 'external'
			? product.type
			: 'simple';

	return PRODUCT_TYPE_COMPATIBLE_FIELD_IDS[ productType ];
}

function isFieldVisibleForProductRelationships(
	fieldId: string,
	products: ProductEntityRecord[]
) {
	if ( ! PRODUCT_EDIT_FIELD_IDS.includes( fieldId as ProductEditFieldId ) ) {
		return true;
	}

	const productEditFieldId = fieldId as ProductEditFieldId;
	const hasVariation = products.some( isProductVariation );

	if (
		hasVariation &&
		PARENT_OWNED_PRODUCT_EDIT_FIELD_ID_SET.has( productEditFieldId )
	) {
		return false;
	}

	const hasVariableParent = products.some( isVariableProductParent );

	if (
		SELLABLE_PRODUCT_EDIT_FIELD_ID_SET.has( productEditFieldId ) &&
		hasVariableParent
	) {
		return false;
	}

	return true;
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

export function getProductEditRecord(
	listedProduct: ProductEntityRecord | undefined,
	rootRecord: ProductEntityRecord | false | undefined,
	rootRecordEdits?: Partial< ProductEntityRecord >
) {
	const editedRootRecord = rootRecord !== false ? rootRecord : undefined;
	const hasRootRecordEdits =
		rootRecordEdits && Object.keys( rootRecordEdits ).length > 0;

	if ( listedProduct && hasRootRecordEdits ) {
		return {
			...listedProduct,
			...rootRecordEdits,
		};
	}

	if ( listedProduct && editedRootRecord ) {
		return {
			...listedProduct,
			...editedRootRecord,
		};
	}

	return listedProduct ?? editedRootRecord;
}

function getCommonProductTypeCompatibleFieldIds(
	products: ProductEntityRecord[]
) {
	if ( products.length === 0 ) {
		return [];
	}

	const [ firstProduct, ...remainingProducts ] = products;
	const remainingCompatibleFieldIdSets = remainingProducts.map(
		( product ) => new Set( getProductTypeCompatibleFieldIds( product ) )
	);

	return getProductTypeCompatibleFieldIds( firstProduct ).filter(
		( fieldId ) =>
			remainingCompatibleFieldIdSets.every( ( compatibleFieldIds ) =>
				compatibleFieldIds.has( fieldId )
			)
	);
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

export function getVisibleProductEditFields(
	fields: ProductField[],
	products: ProductEntityRecord[]
) {
	const compatibleFieldIds =
		getCommonProductTypeCompatibleFieldIds( products );
	const isBulkEdit = products.length > 1;
	const fieldsById = new Map(
		fields.map( ( field ) => [ field.id, field ] )
	);

	return compatibleFieldIds.reduce< ProductField[] >(
		( visibleFields, fieldId ) => {
			const field = fieldsById.get( fieldId );

			if ( ! field ) {
				return visibleFields;
			}

			if (
				isBulkEdit &&
				BULK_UNSUPPORTED_PRODUCT_EDIT_FIELD_ID_SET.has(
					field.id as ProductEditFieldId
				)
			) {
				return visibleFields;
			}

			if (
				! isFieldVisibleForProductRelationships( field.id, products )
			) {
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
		},
		[]
	);
}
