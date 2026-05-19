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

type VariationSource = Pick< ProductVariation, 'id' > & {
	attributes?: Array< {
		option?: string;
		options?: string[];
	} >;
	categories?: ProductEntityRecord[ 'categories' ];
	image?: ProductVariation[ 'image' ] | null;
	images?: ProductEntityRecord[ 'images' ];
	manage_stock?: ProductVariation[ 'manage_stock' ];
	name?: string;
	parent_id?: number;
	slug?: string;
	tags?: ProductEntityRecord[ 'tags' ];
};

function getVariationName( variation: VariationSource ) {
	if ( variation.name ) {
		return variation.name;
	}

	const attributes = variation.attributes ?? [];

	if ( attributes.length > 0 ) {
		return attributes
			.flatMap( ( attr ) => attr.option ?? attr.options ?? [] )
			.join( ', ' );
	}

	return sprintf(
		/* translators: %d: variation ID. */
		__( 'Variation #%d', 'woocommerce' ),
		variation.id
	);
}

function getVariationImages(
	variation: VariationSource
): ProductEntityRecord[ 'images' ] {
	if ( variation.images ) {
		return variation.images;
	}

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
	variation: VariationSource
): VariationEntityRecord {
	return {
		...variation,
		categories: variation.categories ?? [],
		tags: variation.tags ?? [],
		images: getVariationImages( variation ),
		name: getVariationName( variation ),
		slug: variation.slug ?? String( variation.id ),
		type: 'variation',
		manage_stock: variation.manage_stock === true,
	} as unknown as VariationEntityRecord;
}
