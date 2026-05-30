/**
 * External dependencies
 */
import type { FormField } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { VariationEditFieldId } from '../fields/registry';

type VariationFormField = VariationEditFieldId | FormField;

function createFormGroup(
	id: string,
	label: string,
	children: VariationFormField[]
): FormField {
	return { id, label, children };
}

const SALE_SCHEDULE_DATES: FormField = {
	id: 'sale-schedule-dates',
	layout: { type: 'row' as const },
	children: [ 'date_on_sale_from', 'date_on_sale_to' ],
};

const DIMENSIONS: FormField = {
	id: 'dimensions',
	layout: { type: 'row' as const },
	children: [ 'length', 'width', 'height' ],
};

const BACKORDERS_ROW: FormField = {
	id: 'backorders-row',
	layout: { type: 'row' as const },
	children: [ 'allow_backorders', 'low_stock_amount' ],
};

// Unlabeled groups render children in a nested wrapper without a section header,
// allowing CSS to tighten the gap specifically for that inner wrapper.
const GENERAL_CHECKBOXES: FormField = {
	id: 'general-checkboxes',
	children: [ 'downloadable', 'virtual' ],
};

const DOWNLOAD_OPTIONS: FormField = {
	id: 'download-options',
	children: [ 'download_limit', 'download_expiry' ],
};

export const VARIATION_FORM_FIELDS: VariationFormField[] = [
	createFormGroup( 'general-fields', __( 'General', 'woocommerce' ), [
		'variation_active',
		GENERAL_CHECKBOXES,
	] ),
	createFormGroup( 'price-fields', __( 'Price', 'woocommerce' ), [
		'regular_price',
		'sale_price',
		'schedule_sale',
		SALE_SCHEDULE_DATES,
		'cost_of_goods_sold',
		'tax_class',
	] ),
	createFormGroup( 'details-fields', __( 'Details', 'woocommerce' ), [
		'images',
		'description',
	] ),
	createFormGroup(
		'downloadable-files-fields',
		__( 'Downloadable files', 'woocommerce' ),
		[ 'downloadable_files', DOWNLOAD_OPTIONS ]
	),
	createFormGroup( 'inventory-fields', __( 'Inventory', 'woocommerce' ), [
		'sku',
		'global_unique_id',
		'manage_stock',
		'stock_quantity',
		'stock',
		BACKORDERS_ROW,
	] ),
	createFormGroup( 'shipping-fields', __( 'Shipping', 'woocommerce' ), [
		'shipping_class',
		DIMENSIONS,
		'weight',
	] ),
	createFormGroup( 'attributes-fields', __( 'Attributes', 'woocommerce' ), [
		'attributes',
	] ),
];
