/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Badge } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { createProductField, type ProductFieldId } from '../fields/registry';
import type { VariationEntityRecord } from './types';

const REUSED_VARIATION_FIELD_IDS = [
	'name',
	'sku',
	'price',
	'regular_price',
	'sale_price',
	'stock',
	'stock_quantity',
	'manage_stock',
	'variation_active',
	'images',
	'downloadable',
	'weight',
	'length',
	'width',
	'height',
	'shipping_class',
	'tax_status',
] as const satisfies readonly ProductFieldId[];

export const variationFields = REUSED_VARIATION_FIELD_IDS.map( ( id ) => {
	const field = createProductField( id ) as Field< VariationEntityRecord >;

	if ( id === 'name' ) {
		return {
			...field,
			render( { item }: { item: VariationEntityRecord } ) {
				return (
					<span className="woocommerce-variation-name">
						<span className="woocommerce-variation-name__label">
							{ item.name }
						</span>
						{ item.status === 'private' && (
							<Badge intent="none">
								{ __( 'Inactive', 'woocommerce' ) }
							</Badge>
						) }
					</span>
				);
			},
		};
	}

	return field;
} );
