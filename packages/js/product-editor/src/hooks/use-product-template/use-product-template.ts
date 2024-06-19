/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Metadata, ProductTemplate } from '../../types';

const matchesAllTemplateMetaFields = (
	templateMeta: Metadata< string >[],
	productMeta: Metadata< string >[]
) =>
	templateMeta.every( ( item ) =>
		productMeta.find(
			( productMetaEntry ) =>
				productMetaEntry.key === item.key &&
				productMetaEntry.value === item.value
		)
	);

function templateDataMatchesProductData(
	productTemplate: ProductTemplate,
	product: Partial< Product >
): boolean {
	return Object.entries( productTemplate.productData ).every(
		( [ key, value ] ) => {
			if ( key === 'meta_data' ) {
				return matchesAllTemplateMetaFields(
					value,
					product.meta_data || []
				);
			}

			return product[ key ] === value;
		}
	);
}

function findBetterMatchTemplate( matchingTemplates: ProductTemplate[] ) {
	return matchingTemplates.reduce(
		( previous, current ) =>
			Object.keys( current.productData ).length >
			Object.keys( previous.productData ).length
				? current
				: previous,
		matchingTemplates[ 0 ]
	);
}

export const useProductTemplate = (
	productTemplateId: string | undefined,
	product: Partial< Product > | null
) => {
	const productTemplates =
		window.productBlockEditorSettings?.productTemplates ?? [];

	const productType = product?.type;

	// we shouldn't default to the standard-product-template for variations
	if ( ! productTemplateId && productType === 'variation' ) {
		return { productTemplate: null, isResolving: false };
	}

	let matchingProductTemplate: ProductTemplate | undefined;

	if ( productTemplateId ) {
		matchingProductTemplate = productTemplates.find(
			( productTemplate ) => productTemplate.id === productTemplateId
		);
	}

	if ( ! matchingProductTemplate && product ) {
		// Look for matching templates based on product data described on each template.
		const matchingTemplates = productTemplates.filter(
			( productTemplate ) =>
				templateDataMatchesProductData( productTemplate, product )
		);

		// If there are multiple matching templates, we should use the one with the most matching fields.
		// If there is no matching template, we should default to the standard product template.
		matchingProductTemplate =
			findBetterMatchTemplate( matchingTemplates ) ||
			productTemplates.find(
				( productTemplate ) =>
					productTemplate.id === 'standard-product-template'
			);
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
