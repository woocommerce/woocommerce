/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { applyFilters } from '@wordpress/hooks';
import { useSelect } from '@wordpress/data';

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

			return Array.isArray( value )
				? value.includes( product[ key ] )
				: product[ key ] === value;
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

	const productType = product?.type || 'simple';

	const parent = useSelect( ( select ) => {
		if ( ! product || product.type !== 'variation' ) {
			return;
		}
		return select( 'core' ).getEntityRecord(
			'postType',
			'product',
			product.parent_id
		);
	} );

	let matchingProductTemplate = productTemplates.find( ( productTemplate ) =>
		productTemplate.id === productTemplateId &&
		Array.isArray( productTemplate.productData.type )
			? productTemplate.productData.type.includes( productType )
			: productTemplate.productData.type === productType
	);

	if ( ! matchingProductTemplate && product ) {
		// Look for matching templates based on product data described on each template.
		const matchingTemplates = productTemplates.filter(
			( productTemplate ) =>
				templateDataMatchesProductData( productTemplate, product )
		);

		// If there are multiple matching templates, we should use the one with the most matching fields.
		matchingProductTemplate = findBetterMatchTemplate( matchingTemplates );

		if ( product.type === 'variation' && parent ) {
			matchingProductTemplate = applyFilters(
				'woocommerce.product.variations.getMatchingTemplate',
				matchingProductTemplate,
				productTemplates,
				product,
				parent
			) as ProductTemplate;
		}
	}

	// When we switch to getting the product template from the API,
	// this will be needed.
	const isResolving = false;

	return { productTemplate: matchingProductTemplate, isResolving };
};
