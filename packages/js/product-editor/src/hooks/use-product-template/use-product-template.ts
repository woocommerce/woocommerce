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

export const useProductTemplate = ( productTemplateId: string | undefined ) => {
	const productTemplates =
		window.productBlockEditorSettings?.productTemplates ?? [];

	const productTemplateIdToFind =
		productTemplateId || 'standard-product-template';

	const matchingProductTemplate = productTemplates.find(
		( productTemplate ) => productTemplate.id === productTemplateIdToFind
	);

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
