/**
 * Internal dependencies
 */
import type { ProductDataWithId, VariationDataWithId } from './types';

export const isVariation = (
	productData: ProductDataWithId | VariationDataWithId | null
): productData is VariationDataWithId => {
	return productData?.type === 'variation';
};
