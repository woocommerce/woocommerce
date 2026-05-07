/**
 * External dependencies
 */
import type { ProductVariation } from '@woocommerce/data';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import type { VariationEntityRecord } from './types';

function getVariationName( variation: ProductVariation ) {
	if ( variation.name ) {
		return variation.name;
	}

	if ( variation.attributes?.length > 0 ) {
		return variation.attributes.map( ( attr ) => attr.option ).join( ', ' );
	}

	return sprintf(
		/* translators: %d: variation ID. */
		__( 'Variation #%d', 'woocommerce' ),
		variation.id
	);
}

function getVariationImages(
	variation: ProductVariation
): ProductEntityRecord[ 'images' ] {
	if ( ! variation.image ) {
		return [];
	}

	return [
		{
			id: variation.image.id,
			src: variation.image.src,
			alt: variation.image.alt,
			name: variation.image.name,
			thumbnail: variation.image.src,
			date_created: variation.image.date_created || '',
			date_created_gmt: variation.image.date_created_gmt || '',
			date_modified: variation.image.date_modified || '',
			date_modified_gmt: variation.image.date_modified_gmt || '',
		},
	];
}

export function normalizeVariation(
	variation: ProductVariation
): VariationEntityRecord {
	return {
		...variation,
		categories: [],
		tags: [],
		images: getVariationImages( variation ),
		name: getVariationName( variation ),
		slug: String( variation.id ),
		type: 'variation',
		manage_stock: variation.manage_stock === true,
	} as unknown as VariationEntityRecord;
}
