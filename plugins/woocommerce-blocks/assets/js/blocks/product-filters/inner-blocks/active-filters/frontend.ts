/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';

type ActiveFiltersContext = {
	queryId: number;
	params: string[];
};

store( 'woocommerce/product-filter-active', {
	actions: {
		*clearAll() {
			const { params } = getContext< ActiveFiltersContext >();
			const url = new URL( window.location.href );
			const { searchParams } = url;

			params.forEach( ( param ) => searchParams.delete( param ) );
			const { navigate } = yield import( '../product-filter/frontend' );
			yield navigate( url.href );
		},
	},
} );
