/**
 * External dependencies
 */
import { useLayoutTemplates } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { LayoutTemplate } from '../../types';

type UseProductFormTemplatesReturnType = [
	LayoutTemplate[],
	LayoutTemplate[],
	boolean
];

export const useProductFormTemplates =
	(): UseProductFormTemplatesReturnType => {
		// TODO: Add typing to useLayoutTemplates
		const [ layoutTemplates, isResolving ] = useLayoutTemplates() as [
			LayoutTemplate[],
			boolean
		];

		if ( isResolving ) {
			return [ [], [], isResolving ];
		}

		const [
			supportedProductFormTemplates,
			unsupportedProductFormTemplates,
		] = layoutTemplates.reduce< [ LayoutTemplate[], LayoutTemplate[] ] >(
			( [ supported, unsupported ], layoutTemplate ) => {
				if ( layoutTemplate.blockTemplates?.length ) {
					supported.push( layoutTemplate );
				} else {
					unsupported.push( layoutTemplate );
				}
				return [ supported, unsupported ];
			},
			[ [], [] ]
		);

		return [
			supportedProductFormTemplates,
			unsupportedProductFormTemplates,
			isResolving,
		];
	};
