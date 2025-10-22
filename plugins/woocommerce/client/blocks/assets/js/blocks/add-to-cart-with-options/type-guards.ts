/**
 * Internal dependencies
 */
import type { NormalizedProductData, NormalizedVariationData } from './types';

export const isVariation = (
	productData: NormalizedProductData | NormalizedVariationData | null
): productData is NormalizedVariationData => {
	return productData?.type === 'variation';
};
