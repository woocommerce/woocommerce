/**
 * External dependencies
 */
import type { Field, FormField } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';

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
type ProductEditFormField = ProductEditFieldId | FormField;
type ProductType = 'simple' | 'variation' | 'variable' | 'grouped' | 'external';
type ProductVariationEntityRecord = ProductEntityRecord & {
	parent_id: number;
};
type DimensionKey = keyof ProductEntityRecord[ 'dimensions' ];
type Feature = {
	is_enabled?: boolean;
};
type AdminSettings = {
	features?: Record< string, Feature >;
};

const PRODUCT_EDIT_FIELD_IDS = [
	'name',
	'short_description',
	'description',
	'images',
	'images_count',
	'product_status',
	'variation_active',
	'sku',
	'price',
	'regular_price',
	'on_sale',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'cost_of_goods_sold',
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
	'grouped_products',
	'linked_products_count',
] as const;

const DIMENSION_KEYS: DimensionKey[] = [ 'length', 'width', 'height' ];

const DIMENSIONS_FORM_FIELD: ProductEditFormField = {
	id: 'dimensions',
	layout: { type: 'row' as const },
	children: [ 'length', 'width', 'height' ],
};

function createProductEditFormGroup(
	id: string,
	label: string,
	children: ProductEditFormField[]
): ProductEditFormField {
	return {
		id,
		label,
		children,
	};
}

const DOWNLOADABLE_FILES_FORM_FIELD: ProductEditFormField =
	createProductEditFormGroup(
		'downloadable-files-fields',
		__( 'Downloadable files', 'woocommerce' ),
		[ 'downloadable' ]
	);

const SIMPLE_PRODUCT_EDIT_FORM_FIELDS = [
	createProductEditFormGroup(
		'general-fields',
		__( 'General', 'woocommerce' ),
		[ 'name', 'product_status', 'catalog_visibility' ]
	),
	createProductEditFormGroup( 'price-fields', __( 'Price', 'woocommerce' ), [
		'regular_price',
		'sale_price',
		'schedule_sale',
		'cost_of_goods_sold',
	] ),
	createProductEditFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [
		'images',
	] ),
	DOWNLOADABLE_FILES_FORM_FIELD,
	createProductEditFormGroup(
		'inventory-fields',
		__( 'Inventory', 'woocommerce' ),
		[ 'sku', 'manage_stock', 'stock', 'stock_quantity' ]
	),
	createProductEditFormGroup(
		'product-organization-fields',
		__( 'Product organization', 'woocommerce' ),
		[ 'categories', 'brands', 'tags', 'featured' ]
	),
	createProductEditFormGroup(
		'shipping-fields',
		__( 'Shipping', 'woocommerce' ),
		[ 'shipping_class', DIMENSIONS_FORM_FIELD, 'weight' ]
	),
] satisfies ProductEditFormField[];

const VARIATION_PRODUCT_EDIT_FORM_FIELDS = [
	createProductEditFormGroup(
		'general-fields',
		__( 'General', 'woocommerce' ),
		[ 'variation_active' ]
	),
	createProductEditFormGroup( 'price-fields', __( 'Price', 'woocommerce' ), [
		'regular_price',
		'sale_price',
		'schedule_sale',
		'cost_of_goods_sold',
	] ),
	createProductEditFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [
		'images',
	] ),
	DOWNLOADABLE_FILES_FORM_FIELD,
	createProductEditFormGroup(
		'inventory-fields',
		__( 'Inventory', 'woocommerce' ),
		[ 'sku', 'manage_stock', 'stock', 'stock_quantity' ]
	),
	createProductEditFormGroup(
		'shipping-fields',
		__( 'Shipping', 'woocommerce' ),
		[ 'shipping_class', DIMENSIONS_FORM_FIELD, 'weight' ]
	),
] satisfies ProductEditFormField[];

const VARIABLE_PRODUCT_EDIT_FORM_FIELDS = [
	createProductEditFormGroup(
		'general-fields',
		__( 'General', 'woocommerce' ),
		[ 'name', 'product_status', 'catalog_visibility' ]
	),
	createProductEditFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [
		'images',
	] ),
	createProductEditFormGroup(
		'inventory-fields',
		__( 'Inventory', 'woocommerce' ),
		[ 'sku', 'manage_stock', 'stock' ]
	),
	createProductEditFormGroup(
		'product-organization-fields',
		__( 'Product organization', 'woocommerce' ),
		[ 'categories', 'brands', 'tags', 'featured' ]
	),
	createProductEditFormGroup(
		'shipping-fields',
		__( 'Shipping', 'woocommerce' ),
		[ 'shipping_class', DIMENSIONS_FORM_FIELD, 'weight' ]
	),
] satisfies ProductEditFormField[];

const EXTERNAL_PRODUCT_EDIT_FORM_FIELDS = [
	createProductEditFormGroup(
		'general-fields',
		__( 'General', 'woocommerce' ),
		[ 'name', 'product_status', 'catalog_visibility' ]
	),
	createProductEditFormGroup( 'price-fields', __( 'Price', 'woocommerce' ), [
		'regular_price',
		'sale_price',
		'schedule_sale',
	] ),
	createProductEditFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [
		'images',
	] ),
	createProductEditFormGroup(
		'buy-button-fields',
		__( 'Buy button', 'woocommerce' ),
		[ 'external_url', 'button_text' ]
	),
	createProductEditFormGroup(
		'inventory-fields',
		__( 'Inventory', 'woocommerce' ),
		[ 'sku' ]
	),
	createProductEditFormGroup(
		'product-organization-fields',
		__( 'Product organization', 'woocommerce' ),
		[ 'categories', 'brands', 'tags', 'featured' ]
	),
] satisfies ProductEditFormField[];

const GROUPED_PRODUCT_EDIT_FORM_FIELDS = [
	createProductEditFormGroup(
		'general-fields',
		__( 'General', 'woocommerce' ),
		[ 'name', 'product_status', 'catalog_visibility', 'grouped_products' ]
	),
	createProductEditFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [
		'images',
	] ),
	createProductEditFormGroup(
		'inventory-fields',
		__( 'Inventory', 'woocommerce' ),
		[ 'sku' ]
	),
	createProductEditFormGroup(
		'product-organization-fields',
		__( 'Product organization', 'woocommerce' ),
		[ 'categories', 'brands', 'tags', 'featured' ]
	),
] satisfies ProductEditFormField[];

const PRODUCT_TYPE_FORM_FIELDS = {
	simple: SIMPLE_PRODUCT_EDIT_FORM_FIELDS,
	variation: VARIATION_PRODUCT_EDIT_FORM_FIELDS,
	variable: VARIABLE_PRODUCT_EDIT_FORM_FIELDS,
	grouped: GROUPED_PRODUCT_EDIT_FORM_FIELDS,
	external: EXTERNAL_PRODUCT_EDIT_FORM_FIELDS,
} satisfies Record< ProductType, readonly ProductEditFormField[] >;

const PARENT_OWNED_PRODUCT_EDIT_FIELD_ID_SET = new Set< ProductEditFieldId >( [
	'name',
	'short_description',
	'description',
	'catalog_visibility',
	'categories',
	'brands',
	'tags',
	'type',
	'featured',
	'upsell_ids',
	'cross_sell_ids',
	'grouped_products',
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
	'cost_of_goods_sold',
] );

const BULK_UNSUPPORTED_PRODUCT_EDIT_FIELD_ID_SET =
	new Set< ProductEditFieldId >( [ 'sku' ] );

function isCostOfGoodsSoldFeatureEnabled() {
	const adminSettings = getSetting< AdminSettings >( 'admin', {} );
	return Boolean( adminSettings.features?.cost_of_goods_sold?.is_enabled );
}

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

function isRecord( value: unknown ): value is Record< string, unknown > {
	return (
		Boolean( value ) &&
		typeof value === 'object' &&
		! Array.isArray( value )
	);
}

function buildMergedDimensions(
	values: unknown[]
): ProductEntityRecord[ 'dimensions' ] | undefined {
	const hasDimensionValue = values.some( isRecord );

	if ( ! hasDimensionValue ) {
		return undefined;
	}

	const dimensions: Partial< ProductEntityRecord[ 'dimensions' ] > = {};

	DIMENSION_KEYS.forEach( ( dimensionKey ) => {
		const dimensionValues = values.map( ( value ) =>
			isRecord( value ) ? value[ dimensionKey ] : undefined
		);
		const firstDefinedValue = dimensionValues.find(
			( value ) => value !== undefined
		);
		const areValuesEqual = dimensionValues.every(
			( value ) =>
				normalizeValue( value ) ===
				normalizeValue( dimensionValues[ 0 ] )
		);

		dimensions[ dimensionKey ] = (
			areValuesEqual
				? dimensionValues[ 0 ]
				: getMixedValueFallback( firstDefinedValue )
		) as string | undefined;
	} );

	return dimensions as ProductEntityRecord[ 'dimensions' ];
}

function isVariableProductParent( product: ProductEntityRecord ) {
	return product.type === 'variable' && ! product.parent_id;
}

function isProductType( type: string | undefined ): type is ProductType {
	return (
		type === 'simple' ||
		type === 'variation' ||
		type === 'variable' ||
		type === 'grouped' ||
		type === 'external'
	);
}

export function isProductVariation(
	product: ProductEntityRecord
): product is ProductVariationEntityRecord {
	return product.type === 'variation' || Boolean( product.parent_id );
}

function getProductEditFormFieldIds(
	formField: ProductEditFormField
): ProductEditFieldId[] {
	if ( typeof formField === 'string' ) {
		return [ formField ];
	}

	return ( formField.children ?? [] ).flatMap( ( child ) =>
		getProductEditFormFieldIds( child as ProductEditFormField )
	);
}

function getProductType( product: ProductEntityRecord ): ProductType {
	if ( isProductVariation( product ) ) {
		return 'variation';
	}

	return isProductType( product.type ) ? product.type : 'simple';
}

function getProductTypeFieldIds(
	product: ProductEntityRecord
): ProductEditFieldId[] {
	return PRODUCT_TYPE_FORM_FIELDS[ getProductType( product ) ].flatMap(
		getProductEditFormFieldIds
	);
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
		( product ) => new Set( getProductTypeFieldIds( product ) )
	);

	return getProductTypeFieldIds( firstProduct ).filter( ( fieldId ) =>
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

		if ( key === 'dimensions' ) {
			mergedData[ key ] = buildMergedDimensions( values );
			return;
		}

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
				field.id === 'cost_of_goods_sold' &&
				! isCostOfGoodsSoldFeatureEnabled()
			) {
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

function pruneProductEditFormField(
	formField: ProductEditFormField,
	visibleFieldIds: Set< string >
): ProductEditFormField | undefined {
	if ( typeof formField === 'string' ) {
		return visibleFieldIds.has( formField ) ? formField : undefined;
	}

	const children = ( formField.children ?? [] )
		.map( ( child ) =>
			pruneProductEditFormField(
				child as ProductEditFormField,
				visibleFieldIds
			)
		)
		.filter(
			( child ): child is ProductEditFormField => child !== undefined
		);

	if ( children.length === 0 ) {
		return undefined;
	}

	return {
		...formField,
		children,
	};
}

export function getProductTypeFormFields(
	products: ProductEntityRecord[],
	visibleFields?: ProductField[]
): Array< FormField | string > {
	const [ firstProduct ] = products;

	if ( ! firstProduct ) {
		return [];
	}

	const formFields = [
		...PRODUCT_TYPE_FORM_FIELDS[ getProductType( firstProduct ) ],
	];

	if ( ! visibleFields ) {
		return formFields;
	}

	const visibleFieldIds = new Set(
		visibleFields.map( ( field ) => field.id )
	);

	return formFields
		.map( ( formField ) =>
			pruneProductEditFormField( formField, visibleFieldIds )
		)
		.filter(
			( formField ): formField is ProductEditFormField =>
				formField !== undefined
		);
}
