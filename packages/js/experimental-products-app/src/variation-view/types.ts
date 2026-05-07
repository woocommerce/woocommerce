/**
 * External dependencies
 */
import type { ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';

export type VariationEntityRecord = Omit<
	ProductEntityRecord,
	'attributes' | 'images' | 'manage_stock' | 'type'
> &
	Omit< ProductVariation, 'manage_stock' | 'type' > & {
		attributes: ProductVariation[ 'attributes' ];
		images: ProductEntityRecord[ 'images' ];
		image?: ProductVariation[ 'image' ] | null;
		manage_stock: boolean;
		parent_id: number;
		slug: string;
		type: 'variation';
	};
