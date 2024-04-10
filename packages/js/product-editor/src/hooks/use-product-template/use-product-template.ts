/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Metadata, ProductTemplate } from '../../types';

declare global {
	interface Window {
		productBlockEditorSettings: {
			productTemplates: ProductTemplate[];
		};
	}
}

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
	product: Partial< Product > | undefined | null
) => {
	const productTemplates =
		window.productBlockEditorSettings?.productTemplates ?? [];

	const productType = product?.type;

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

	if ( ! matchingProductTemplate && product ) {
		// Look for matching templates based on product data described on each template.
		const matchingTemplates = productTemplates.filter(
			( productTemplate ) =>
				templateDataMatchesProductData( productTemplate, product )
		);

		console.log( 'matchingTemplates', product, matchingTemplates );
		// If there are multiple matching templates, we should use the one with the most matching fields.
		matchingProductTemplate = findBetterMatchTemplate( matchingTemplates );
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
