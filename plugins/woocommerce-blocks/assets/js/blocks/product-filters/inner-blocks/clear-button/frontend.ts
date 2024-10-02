/**
 * Logic in this file is unused and should be moved to product-fitlers block.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/50868
 */
/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';

const getQueryParams = ( e: Event ) => {
	const filterNavContainer = ( e.target as HTMLElement )?.closest(
		'nav.wp-block-woocommerce-product-filter'
	);

	const filterContainer = filterNavContainer?.querySelector(
		'[data-block-name][data-has-filter="yes"]'
	);

	const filter = filterContainer?.getAttribute( 'data-block-name' );

	switch ( filter ) {
		case 'woocommerce/product-filter-attribute':
			const wcContext =
				filterContainer?.getAttribute( 'data-wc-context' );
			const queryParams = wcContext ? JSON.parse( wcContext ) : null;

			return [
				`filter_${ queryParams?.attributeSlug }`,
				`query_type_${ queryParams?.attributeSlug }`,
			];
		case 'woocommerce/product-filter-price':
			return [ 'min_price', 'max_price' ];
		case 'woocommerce/product-filter-rating':
			return [ 'rating_filter' ];
		case 'woocommerce/product-filter-stock-status':
			return [ 'filter_stock_status' ];
	}
};

type ProductFilterContext = {
	hasSelectedFilter: boolean;
};

store( 'woocommerce/product-filter', {
	actions: {
		*clear( e: Event ) {
			const params = getQueryParams( e );

			const url = new URL( window.location.href );
			const { searchParams } = url;
			let needsNavigate = false;

			params?.forEach( ( param ) => {
				if ( searchParams.get( param ) !== null ) {
					searchParams.delete( param );
					needsNavigate = true;
				}
			} );

			const productFilterContext = getContext< ProductFilterContext >(
				'woocommerce/product-filter'
			);

			productFilterContext.hasSelectedFilter = false;

			if ( needsNavigate ) {
				const { navigate } = yield import(
					'../product-filter/frontend'
				);
				yield navigate( url.href );
			}
		},
	},
} );
