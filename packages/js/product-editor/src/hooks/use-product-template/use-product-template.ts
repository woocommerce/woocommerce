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
		window.productBlockEditorSettings.productTemplates ?? [];
	const productTemplate = productTemplates.find( ( template ) => {
		if ( productTemplateId === template.id ) {
			return true;
		}

		if ( ! productType ) {
			return false;
		}

		// Fallback to the product type if the product does not have any product
		// template associated to itself.
		return template.productData.type === productType;
	} );

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate, isResolving };
};
