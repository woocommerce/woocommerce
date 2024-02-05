/**
 * External dependencies
 */
import { ProductType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductTemplate } from '../../types';

declare global {
	interface Window {
		productBlockEditorSettings: {
			productTemplates: ProductTemplate[];
		};
	}
}

export const useProductTemplate = (
	productTemplateId: string | undefined,
	productType: ProductType | undefined
) => {
	const productTemplates =
		window.productBlockEditorSettings?.productTemplates ?? [];

	const productTemplateIdToFind =
		productType === 'variable'
			? 'standard-product-template'
			: productTemplateId;

	const productTypeToFind =
		productType === 'variable' ? 'simple' : productType;

	let matchingProductTemplate = productTemplates.find(
		( productTemplate ) =>
			productTemplate.id === productTemplateIdToFind &&
			productTemplate.productData.type === productTypeToFind
	);

	if ( ! matchingProductTemplate ) {
		// Fallback to the first template with the same product type.
		matchingProductTemplate = productTemplates.find(
			( productTemplate ) =>
				productTemplate.productData.type === productTypeToFind
		);
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
