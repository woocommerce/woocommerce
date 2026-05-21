/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { getCurrencyObject } from '../fields/utils/currency';
import { validatePrice } from '../fields/price/utils';
import { validateSalePrice } from '../fields/sale_price/validation';
import { buildMergedProductEditData } from './utils';

type ProductField = Field< ProductEntityRecord >;
type DimensionFieldId = keyof ProductEntityRecord[ 'dimensions' ];

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

export const BULK_EDIT_MIXED_LABEL = __( 'Mixed', 'woocommerce' );

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

const FIELD_DATA_KEYS: Record< string, string > = {
	product_status: 'status',
	stock: 'stock_status',
};

const DIMENSION_FIELD_ID_SET = new Set< DimensionFieldId >( [
	'length',
	'width',
	'height',
] );

export function getBulkNumericOperationFieldId( fieldId: BulkNumericFieldId ) {
	return `${ fieldId }${ BULK_NUMERIC_OPERATION_FIELD_SUFFIX }`;
}

export function isBulkNumericOperationFieldId( fieldId: string ) {
	return fieldId.endsWith( BULK_NUMERIC_OPERATION_FIELD_SUFFIX );
}

function normalizeValue( value: unknown ) {
	if ( value === undefined ) {
		return '__undefined__';
	}

	return JSON.stringify( value );
}

function getFieldDataKey( fieldId: string ) {
	return FIELD_DATA_KEYS[ fieldId ] ?? fieldId;
}

function getDefinedCostValue( product: ProductEntityRecord ) {
	return product.cost_of_goods_sold?.values?.[ 0 ]?.defined_value;
}

function isDimensionFieldId( fieldId: string ): fieldId is DimensionFieldId {
	return DIMENSION_FIELD_ID_SET.has( fieldId as DimensionFieldId );
}

function getProductFieldValue(
	product: ProductEntityRecord,
	field: ProductField
) {
	if ( field.id === 'cost_of_goods_sold' ) {
		return getDefinedCostValue( product );
	}

	if ( isDimensionFieldId( field.id ) ) {
		return product.dimensions?.[ field.id ] ?? '';
	}

	if ( field.getValue ) {
		return field.getValue( { item: product } );
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

export function isBulkNumericPercentOperation( operation: unknown ) {
	return operation === 'increase_percent' || operation === 'decrease_percent';
}

export function isBulkNumericPercentEdit(
	data: ProductBulkEditFormData,
	fieldId: string
) {
	if ( ! isBulkNumericFieldId( fieldId ) ) {
		return false;
	}

	return isBulkNumericPercentOperation(
		getBulkNumericEditFromData( data, fieldId ).operation
	);
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
