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

	let matchingProductTemplate = productTemplates.find(
		( productTemplate ) => productTemplateId === productTemplate.id
	);

	if ( ! matchingProductTemplate ) {
		// Fallback to the first template with the same product type.
		matchingProductTemplate = productTemplates.find(
			( productTemplate ) =>
				productTemplate.productData.type === productType
		);
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
