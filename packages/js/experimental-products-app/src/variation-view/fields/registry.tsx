/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import {
	createProductFields,
	type ProductFieldId,
} from '../../fields/registry';
import { fieldExtensions as allowBackordersFieldExtensions } from './allow_backorders/field';
import { fieldExtensions as attributesFieldExtensions } from './attributes/field';
import { fieldExtensions as downloadableFieldExtensions } from './downloadable/field';
import { fieldExtensions as downloadableFilesFieldExtensions } from './downloadable_files/field';
import { fieldExtensions as downloadExpiryFieldExtensions } from './download_expiry/field';
import { fieldExtensions as downloadLimitFieldExtensions } from './download_limit/field';
import { fieldExtensions as lowStockAmountFieldExtensions } from './low_stock_amount/field';
import { fieldExtensions as shippingClassFieldExtensions } from './shipping_class/field';
import {
	createVariationDimensionField,
	createVariationWeightField,
} from './components/dimension';
import { fieldExtensions as taxClassFieldExtensions } from './tax_class/field';
import { fieldExtensions as virtualFieldExtensions } from './virtual/field';
import type { ProductEntityRecord } from './types';

type VariationEditField = Field< ProductEntityRecord >;

// Fields pulled directly from the main registry with no modifications.
const SHARED_FIELD_IDS: readonly ProductFieldId[] = [
	'description',
	'sku',
	'global_unique_id',
	'regular_price',
	'sale_price',
	'schedule_sale',
	'date_on_sale_from',
	'date_on_sale_to',
	'cost_of_goods_sold',
	'images',
	'manage_stock',
	'stock',
	'stock_quantity',
	'tax_status',
	'variation_active',
] as const;

// Shipping fields get an extra isVisible so they disappear when virtual=true.
const SHIPPING_FIELD_IDS: readonly ProductFieldId[] = [
	'shipping_class',
	'weight',
	'length',
	'width',
	'height',
] as const;

function withVirtualGuard( field: VariationEditField ): VariationEditField {
	const existing = field.isVisible;
	return {
		...field,
		isVisible: ( item: ProductEntityRecord ) =>
			! item.virtual && ( existing === undefined || existing( item ) ),
	};
}

const sharedFields: VariationEditField[] =
	createProductFields( SHARED_FIELD_IDS );

const variationShippingOverrides: Record<
	string,
	Partial< VariationEditField >
> = {
	shipping_class: shippingClassFieldExtensions,
	length: createVariationDimensionField( 'length' ),
	width: createVariationDimensionField( 'width' ),
	height: createVariationDimensionField( 'height' ),
	weight: createVariationWeightField(),
};

const shippingFields: VariationEditField[] = createProductFields(
	SHIPPING_FIELD_IDS
)
	.map( ( field ) => {
		const override = variationShippingOverrides[ field.id as string ];
		if ( override?.Edit ) {
			return { ...field, Edit: override.Edit };
		}
		return field;
	} )
	.map( withVirtualGuard );

// Variation-exclusive field IDs — these do not exist in the main registry.
const VARIATION_ONLY_FIELD_IDS = [
	'downloadable',
	'tax_class',
	'virtual',
	'allow_backorders',
	'low_stock_amount',
	'downloadable_files',
	'download_limit',
	'download_expiry',
	'attributes',
] as const;

export type VariationOnlyFieldId =
	( typeof VARIATION_ONLY_FIELD_IDS )[ number ];

const VARIATION_ONLY_FIELD_EXTENSIONS: Record<
	VariationOnlyFieldId,
	Partial< VariationEditField >
> = {
	downloadable: downloadableFieldExtensions,
	tax_class: taxClassFieldExtensions,
	virtual: virtualFieldExtensions,
	allow_backorders: allowBackordersFieldExtensions,
	low_stock_amount: lowStockAmountFieldExtensions,
	downloadable_files: downloadableFilesFieldExtensions,
	download_limit: downloadLimitFieldExtensions,
	download_expiry: downloadExpiryFieldExtensions,
	attributes: attributesFieldExtensions,
};

const variationOnlyFields: VariationEditField[] = VARIATION_ONLY_FIELD_IDS.map(
	( id ) => ( { id, ...VARIATION_ONLY_FIELD_EXTENSIONS[ id ] } )
);

// The full field list used by the variation edit drawer.
export const variationEditFields: VariationEditField[] = [
	...sharedFields,
	...shippingFields,
	...variationOnlyFields,
];

// Union type of all field IDs available in the variation panel.
export type VariationEditFieldId = ProductFieldId | VariationOnlyFieldId;
