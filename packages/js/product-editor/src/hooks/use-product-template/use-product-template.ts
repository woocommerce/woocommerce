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
	productType: ProductType | undefined,
	postType: string | undefined
) {
	if ( productTemplate.postType !== postType ) {
		return false;
	}

	if (
		( productTemplate.productData.type ?? null ) === ( productType ?? null )
	) {
		return true;
	}

	return productTemplate.alternateProductDatas?.some(
		( alternateProductData ) =>
			( alternateProductData.type ?? null ) === ( productType ?? null )
	);
}

export const useProductTemplate = (
	productTemplateId: string | undefined,
	productType: ProductType | undefined,
	postType: string | undefined
) => {
	const productTemplates =
		window.productBlockEditorSettings?.productTemplates ?? [];

	let matchingProductTemplate = productTemplates.find(
		( productTemplate ) =>
			productTemplate.id === productTemplateId &&
			isProductTypeSupported( productTemplate, productType, postType )
	);

	if ( ! matchingProductTemplate ) {
		// Fallback to the first template with the same product type.
		matchingProductTemplate = productTemplates.find( ( productTemplate ) =>
			isProductTypeSupported( productTemplate, productType, postType )
		);
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
