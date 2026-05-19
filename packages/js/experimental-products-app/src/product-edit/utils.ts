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
import { getCurrencyObject } from '../fields/utils/currency';
import { validatePrice } from '../fields/price/utils';
import { validateSalePrice } from '../fields/sale_price/validation';

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
export type ProductBulkEditFieldState = {
	isEmpty: boolean;
	isMixed: boolean;
	value: unknown;
	placeholder?: string;
};
export type ProductBulkEditData = {
	data: ProductEntityRecord;
	fieldStates: Record< string, ProductBulkEditFieldState >;
};
export type BulkNumericFieldId =
	| 'regular_price'
	| 'sale_price'
	| 'cost_of_goods_sold'
	| 'stock_quantity';
export type BulkNumericOperation =
	| 'dont_change'
	| 'set'
	| 'increase'
	| 'decrease'
	| 'increase_percent'
	| 'decrease_percent';
export type BulkNumericEdit = {
	operation: BulkNumericOperation;
	value: string;
};
export type ProductBulkEditFormData = ProductEntityRecord &
	Record< string, unknown >;
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
	'linked_products_count',
] as const;

const DIMENSION_GROUP_FIELD_IDS = [ 'weight', 'length', 'width' ] as const;

const DIMENSIONS_FORM_FIELD: ProductEditFormField = {
	id: 'dimensions',
	layout: { type: 'row' as const },
	children: [ ...DIMENSION_GROUP_FIELD_IDS ],
};

const PARENT_DIMENSIONS_FORM_FIELD: ProductEditFormField = {
	id: 'parent-dimensions',
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
		{
			id: 'sale-schedule-dates',
			layout: { type: 'row' as const },
			children: [ 'date_on_sale_from', 'date_on_sale_to' ],
		},
		'cost_of_goods_sold',
	] ),
	createProductEditFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [
		'images',
	] ),
	DOWNLOADABLE_FILES_FORM_FIELD,
	createProductEditFormGroup(
		'inventory-fields',
		__( 'Inventory', 'woocommerce' ),
		[ 'sku', 'stock', 'manage_stock', 'stock_quantity' ]
	),
	createProductEditFormGroup(
		'product-organization-fields',
		__( 'Product organization', 'woocommerce' ),
		[ 'categories', 'brands', 'tags', 'featured' ]
	),
	createProductEditFormGroup(
		'shipping-fields',
		__( 'Shipping', 'woocommerce' ),
		[ 'shipping_class', DIMENSIONS_FORM_FIELD, 'height' ]
	),
] satisfies ProductEditFormField[];

const VARIATION_PRODUCT_EDIT_FORM_FIELDS = [
	createProductEditFormGroup(
		'general-fields',
		__( 'General', 'woocommerce' ),
		[ 'product_status' ]
	),
	createProductEditFormGroup( 'price-fields', __( 'Price', 'woocommerce' ), [
		'regular_price',
		'sale_price',
		'schedule_sale',
		{
			id: 'sale-schedule-dates',
			layout: { type: 'row' as const },
			children: [ 'date_on_sale_from', 'date_on_sale_to' ],
		},
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
		[ 'shipping_class', DIMENSIONS_FORM_FIELD, 'height' ]
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
		[ 'shipping_class', PARENT_DIMENSIONS_FORM_FIELD, 'weight' ]
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
		{
			id: 'sale-schedule-dates',
			layout: { type: 'row' as const },
			children: [ 'date_on_sale_from', 'date_on_sale_to' ],
		},
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
		[ 'name', 'product_status', 'catalog_visibility', 'upsell_ids' ]
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

export const BULK_EDIT_MIXED_LABEL = __( '(Mixed)', 'woocommerce' );

export const DEFAULT_BULK_NUMERIC_EDIT: BulkNumericEdit = {
	operation: 'dont_change',
	value: '',
};

const BULK_NUMERIC_OPERATION_FIELD_SUFFIX = '__bulk_operation';

const BULK_NUMERIC_FIELD_ID_SET = new Set< BulkNumericFieldId >( [
	'regular_price',
	'sale_price',
	'cost_of_goods_sold',
	'stock_quantity',
] );

const BULK_MONEY_FIELD_ID_SET = new Set< BulkNumericFieldId >( [
	'regular_price',
	'sale_price',
	'cost_of_goods_sold',
] );

const FIELD_DATA_KEYS: Partial< Record< ProductEditFieldId, string > > = {
	product_status: 'status',
	stock: 'stock_status',
};

export function getBulkNumericOperationFieldId( fieldId: BulkNumericFieldId ) {
	return `${ fieldId }${ BULK_NUMERIC_OPERATION_FIELD_SUFFIX }`;
}

export function isBulkNumericOperationFieldId( fieldId: string ) {
	return fieldId.endsWith( BULK_NUMERIC_OPERATION_FIELD_SUFFIX );
}

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

function getFieldDataKey( fieldId: string ) {
	return FIELD_DATA_KEYS[ fieldId as ProductEditFieldId ] ?? fieldId;
}

function getDefinedCostValue( product: ProductEntityRecord ) {
	return product.cost_of_goods_sold?.values?.[ 0 ]?.defined_value;
}

function getProductFieldValue(
	product: ProductEntityRecord,
	field: ProductField
) {
	if ( field.id === 'cost_of_goods_sold' ) {
		return getDefinedCostValue( product );
	}

	const dataKey = getFieldDataKey( field.id );

	return product[ dataKey as keyof ProductEntityRecord ];
}

function isEmptyBulkValue( value: unknown ) {
	if ( value === undefined || value === null || value === '' ) {
		return true;
	}

	if ( Array.isArray( value ) ) {
		return value.length === 0;
	}

	return false;
}

function getBulkNumericValue(
	product: ProductEntityRecord,
	fieldId: BulkNumericFieldId
) {
	if ( fieldId === 'cost_of_goods_sold' ) {
		return getDefinedCostValue( product );
	}

	return product[ fieldId ];
}

function toFiniteNumber( value: unknown ) {
	if ( value === '' || value === null || value === undefined ) {
		return undefined;
	}

	const numberValue = Number( value );

	return Number.isFinite( numberValue ) ? numberValue : undefined;
}

function getPrecisionMultiplier() {
	return Math.pow( 10, getCurrencyObject().precision );
}

function roundMoneyValue( value: number ) {
	const multiplier = getPrecisionMultiplier();

	return Math.round( value * multiplier ) / multiplier;
}

function formatMoneyValue( value: number ) {
	return roundMoneyValue( value ).toFixed( getCurrencyObject().precision );
}

function formatStockQuantityValue( value: number ) {
	return Math.round( value );
}

function clampBulkNumericValue( value: number ) {
	return Math.max( 0, value );
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

export function buildProductBulkEditData(
	products: ProductEntityRecord[],
	fields: ProductField[]
): ProductBulkEditData {
	const data = buildMergedProductEditData( products );
	const fieldStates = fields.reduce<
		Record< string, ProductBulkEditFieldState >
	>( ( states, field ) => {
		const values = products.map( ( product ) =>
			getProductFieldValue( product, field )
		);
		const firstValue = values[ 0 ];
		const areValuesEqual = values.every(
			( value ) =>
				normalizeValue( value ) === normalizeValue( firstValue )
		);
		const isEmpty = areValuesEqual && isEmptyBulkValue( firstValue );

		states[ field.id ] = {
			isEmpty,
			isMixed: ! areValuesEqual,
			value: areValuesEqual ? firstValue : undefined,
			placeholder: ! areValuesEqual ? BULK_EDIT_MIXED_LABEL : undefined,
		};

		return states;
	}, {} );

	return {
		data,
		fieldStates,
	};
}

export function isBulkNumericFieldId(
	fieldId: string
): fieldId is BulkNumericFieldId {
	return BULK_NUMERIC_FIELD_ID_SET.has( fieldId as BulkNumericFieldId );
}

export function isBulkNumericEditPending( edit?: BulkNumericEdit ) {
	return Boolean( edit && edit.operation !== 'dont_change' );
}

export function getBulkNumericOperations(
	fieldId: BulkNumericFieldId
): BulkNumericOperation[] {
	const baseOperations: BulkNumericOperation[] = [
		'dont_change',
		'set',
		'increase',
		'decrease',
	];

	if ( fieldId === 'stock_quantity' ) {
		return baseOperations;
	}

	return [ ...baseOperations, 'increase_percent', 'decrease_percent' ];
}

export function isBulkNumericEditValid(
	fieldId: BulkNumericFieldId,
	edit?: BulkNumericEdit
) {
	if ( ! isBulkNumericEditPending( edit ) ) {
		return true;
	}

	const numberValue = toFiniteNumber( edit?.value );

	if ( numberValue === undefined || numberValue < 0 ) {
		return false;
	}

	if ( fieldId === 'stock_quantity' && ! Number.isInteger( numberValue ) ) {
		return false;
	}

	return true;
}

function getBulkNumericOperationFromData(
	data: ProductBulkEditFormData,
	fieldId: BulkNumericFieldId
): BulkNumericOperation {
	const operation =
		data[ getBulkNumericOperationFieldId( fieldId ) ] ??
		DEFAULT_BULK_NUMERIC_EDIT.operation;

	return typeof operation === 'string' &&
		getBulkNumericOperations( fieldId ).includes(
			operation as BulkNumericOperation
		)
		? ( operation as BulkNumericOperation )
		: DEFAULT_BULK_NUMERIC_EDIT.operation;
}

export function getBulkNumericEditFromData(
	data: ProductBulkEditFormData,
	fieldId: BulkNumericFieldId
): BulkNumericEdit {
	const value =
		fieldId === 'cost_of_goods_sold'
			? getDefinedCostValue( data )
			: data[ fieldId ];

	return {
		operation: getBulkNumericOperationFromData( data, fieldId ),
		value: value === undefined || value === null ? '' : String( value ),
	};
}

export function getBulkNumericEditsFromData(
	data: ProductBulkEditFormData
): Partial< Record< BulkNumericFieldId, BulkNumericEdit > > {
	return Array.from( BULK_NUMERIC_FIELD_ID_SET ).reduce<
		Partial< Record< BulkNumericFieldId, BulkNumericEdit > >
	>( ( edits, fieldId ) => {
		edits[ fieldId ] = getBulkNumericEditFromData( data, fieldId );
		return edits;
	}, {} );
}

function calculateBulkNumericValue(
	currentValue: unknown,
	edit: BulkNumericEdit
) {
	const editValue = toFiniteNumber( edit.value );

	if ( editValue === undefined ) {
		return undefined;
	}

	if ( edit.operation === 'set' ) {
		return editValue;
	}

	const currentNumber = toFiniteNumber( currentValue );

	if ( currentNumber === undefined ) {
		return undefined;
	}

	switch ( edit.operation ) {
		case 'increase':
			return currentNumber + editValue;
		case 'decrease':
			return currentNumber - editValue;
		case 'increase_percent':
			return currentNumber + currentNumber * ( editValue / 100 );
		case 'decrease_percent':
			return currentNumber - currentNumber * ( editValue / 100 );
		default:
			return undefined;
	}
}

function getUpdatedCostOfGoodsSold(
	product: ProductEntityRecord,
	value: string
): ProductEntityRecord[ 'cost_of_goods_sold' ] {
	const costOfGoodsSold = product.cost_of_goods_sold ?? {};
	const [ firstValue = {}, ...remainingValues ] =
		costOfGoodsSold.values ?? [];

	return {
		...costOfGoodsSold,
		values: [
			{
				...firstValue,
				defined_value: value,
			},
			...remainingValues,
		],
	};
}

export function getBulkNumericChangesForProduct(
	product: ProductEntityRecord,
	edits: Partial< Record< BulkNumericFieldId, BulkNumericEdit > >
): Partial< ProductEntityRecord > {
	const changes: Partial< ProductEntityRecord > = {};

	Object.entries( edits ).forEach( ( [ fieldId, edit ] ) => {
		if (
			! edit ||
			! isBulkNumericFieldId( fieldId ) ||
			! isBulkNumericEditPending( edit ) ||
			! isBulkNumericEditValid( fieldId, edit )
		) {
			return;
		}

		const calculatedValue = calculateBulkNumericValue(
			getBulkNumericValue( product, fieldId ),
			edit
		);

		if ( calculatedValue === undefined ) {
			return;
		}

		const clampedValue = clampBulkNumericValue( calculatedValue );

		if ( fieldId === 'stock_quantity' ) {
			changes.stock_quantity = formatStockQuantityValue( clampedValue );
			return;
		}

		const nextValue = BULK_MONEY_FIELD_ID_SET.has( fieldId )
			? formatMoneyValue( clampedValue )
			: String( clampedValue );

		if ( fieldId === 'cost_of_goods_sold' ) {
			changes.cost_of_goods_sold = getUpdatedCostOfGoodsSold(
				product,
				nextValue
			);
			return;
		}

		changes[ fieldId ] = nextValue;
	} );

	return changes;
}

export function validateBulkNumericEdits(
	products: ProductEntityRecord[],
	edits: Partial< Record< BulkNumericFieldId, BulkNumericEdit > >
) {
	for ( const product of products ) {
		const projectedProduct = {
			...product,
			...getBulkNumericChangesForProduct( product, edits ),
		};
		const regularPriceError = validatePrice(
			projectedProduct.regular_price
		);

		if ( regularPriceError ) {
			return regularPriceError;
		}

		const salePriceError = validateSalePrice( projectedProduct );

		if ( salePriceError ) {
			return salePriceError;
		}

		const costOfGoodsSoldError = validatePrice(
			getDefinedCostValue( projectedProduct )
		);

		if ( costOfGoodsSoldError ) {
			return costOfGoodsSoldError;
		}
	}

	return null;
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
