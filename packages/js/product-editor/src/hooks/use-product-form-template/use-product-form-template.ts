/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import {
	useLayoutTemplate,
	useLayoutTemplates,
} from '@woocommerce/block-templates';
import { ProductType } from '@woocommerce/data';

export const useProductFormTemplate = (
	productFormTemplateId: string | undefined,
	productType: ProductType | undefined,
	postType: string | undefined
) => {
	const [ layoutTemplates ] = useLayoutTemplates();

	const bestMatchingLayoutTemplateId = useMemo( () => {
		if ( ! layoutTemplates ) {
			return null;
		}

		let matchingLayoutTemplate = layoutTemplates.find(
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			( layoutTemplate: any ) =>
				layoutTemplate.id === productFormTemplateId &&
				layoutTemplate.productData?.type === productType
		);

		if ( ! matchingLayoutTemplate ) {
			matchingLayoutTemplate = layoutTemplates.find(
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				( layoutTemplate: any ) =>
					layoutTemplate.productData?.type === productType
			);
		}

		return matchingLayoutTemplate?.id;
	}, [ layoutTemplates, productFormTemplateId, productType, postType ] );

	const { layoutTemplate, isResolving: isLayoutTemplateResolving } =
		useLayoutTemplate( bestMatchingLayoutTemplateId );

	return [ layoutTemplate, isLayoutTemplateResolving ];
};
