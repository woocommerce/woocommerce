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

function isProductTypeSupported(
	productTemplate: ProductTemplate,
	productType: ProductType | undefined
) {
	if ( productTemplate.productData.type === productType ) {
		return true;
	}

	const alternateProductDatas = productTemplate.alternateProductDatas;
	if ( alternateProductDatas ) {
		return alternateProductDatas.some(
			( alternateProductData ) =>
				alternateProductData.type === productType
		);
	}

	return false;
}

export const useProductTemplate = (
	productTemplateId: string | undefined,
	productType: ProductType | undefined
) => {
	const productTemplates =
		window.productBlockEditorSettings?.productTemplates ?? [];

	let matchingProductTemplate = productTemplates.find(
		( productTemplate ) =>
			productTemplate.id === productTemplateId &&
			isProductTypeSupported( productTemplate, productType )
	);

	if ( ! matchingProductTemplate ) {
		// Fallback to the first template with the same product type.
		matchingProductTemplate = productTemplates.find( ( productTemplate ) =>
			isProductTypeSupported( productTemplate, productType )
		);
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
