/**
 * External dependencies
 */
import { store, getContext, getElement } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersContext } from '../../frontend';

type AttributeFilterContext = {
	attributeSlug: string;
	queryType: 'or' | 'and';
	selectType: 'single' | 'multiple';
};

store( 'woocommerce/product-filter-attribute', {
	actions: {
		toggleFilter: () => {
			const { ref } = getElement();
			const targetAttribute =
				ref.getAttribute( 'data-attribute-value' ) ?? 'value';
			const termSlug = ref.getAttribute( targetAttribute );

			if ( ! termSlug ) return;

			const { attributeSlug, queryType } =
				getContext< AttributeFilterContext >();
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);

			if (
				! (
					`filter_${ attributeSlug }` in productFiltersContext.params
				)
			) {
				productFiltersContext.params = {
					...productFiltersContext.params,
					[ `filter_${ attributeSlug }` ]: termSlug,
					[ `query_type_${ attributeSlug }` ]: queryType,
				};
				return;
			}

			const selectedTerms =
				productFiltersContext.params[
					`filter_${ attributeSlug }`
				].split( ',' );
			if ( selectedTerms.includes( termSlug ) ) {
				const remainingSelectedTerms = selectedTerms.filter(
					( term ) => term !== termSlug
				);
				if ( remainingSelectedTerms.length > 0 ) {
					productFiltersContext.params[
						`filter_${ attributeSlug }`
					] = remainingSelectedTerms.join( ',' );
				} else {
					const updatedParams = productFiltersContext.params;

					delete updatedParams[ `filter_${ attributeSlug }` ];
					delete updatedParams[ `query_type_${ attributeSlug }` ];

					productFiltersContext.params = updatedParams;
				}
			} else {
				productFiltersContext.params[ `filter_${ attributeSlug }` ] =
					selectedTerms.concat( termSlug ).join( ',' );
			}
		},

		clearFilters: () => {
			const { attributeSlug } = getContext< AttributeFilterContext >();
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			const updatedParams = productFiltersContext.params;

			delete updatedParams[ `filter_${ attributeSlug }` ];
			delete updatedParams[ `query_type_${ attributeSlug }` ];

			productFiltersContext.params = updatedParams;
		},
	},
} );
