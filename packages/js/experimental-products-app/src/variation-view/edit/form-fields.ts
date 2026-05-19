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
): VariationFormField {
	return {
		id,
		label,
		children,
	};
}

const DIMENSIONS_FORM_FIELD: VariationFormField = {
	id: 'dimensions',
	layout: { type: 'row' as const },
	children: [ 'length', 'width', 'height' ],
};

const DOWNLOADABLE_FILES_FORM_FIELD: VariationFormField = createFormGroup(
	'downloadable-files-fields',
	__( 'Downloadable files', 'woocommerce' ),
	[ 'downloadable' ]
);

export const VARIATION_FORM_FIELDS = [
	createFormGroup( 'general-fields', __( 'General', 'woocommerce' ), [
		'product_status',
	] ),
	createFormGroup( 'price-fields', __( 'Price', 'woocommerce' ), [
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
	createFormGroup( 'image-fields', __( 'Images', 'woocommerce' ), [ 'images' ] ),
	DOWNLOADABLE_FILES_FORM_FIELD,
	createFormGroup( 'inventory-fields', __( 'Inventory', 'woocommerce' ), [
		'sku',
		'manage_stock',
		'stock',
		'stock_quantity',
	] ),
	createFormGroup( 'shipping-fields', __( 'Shipping', 'woocommerce' ), [
		'shipping_class',
		DIMENSIONS_FORM_FIELD,
		'weight',
	] ),
] satisfies VariationFormField[];
