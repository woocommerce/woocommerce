/**
 * External dependencies
 */
import {
	getContext,
	store,
	navigate as navigateFn,
} from '@woocommerce/interactivity';
import { getSetting } from '@woocommerce/settings';

const isBlockTheme = getSetting< boolean >( 'isBlockTheme' );
const isProductArchive = getSetting< boolean >( 'isProductArchive' );
const needsRefresh = getSetting< boolean >(
	'needsRefreshForInteractivityAPI',
	false
);

function isParamsEqual(
	obj1: Record< string, string >,
	obj2: Record< string, string >
): boolean {
	const keys1 = Object.keys( obj1 );
	const keys2 = Object.keys( obj2 );

	// First check if both objects have the same number of keys
	if ( keys1.length !== keys2.length ) {
		return false;
	}

	// Check if all keys and values are the same
	for ( const key of keys1 ) {
		if ( obj1[ key ] !== obj2[ key ] ) {
			return false;
		}
	}

	return true;
}

export function navigate( href: string, options = {} ) {
	/**
	 * We may need to reset the current page when changing filters.
	 * This is because the current page may not exist for this set
	 * of filters and will 404 when the user navigates to it.
	 *
	 * There are different pagination formats to consider, as documented here:
	 * https://github.com/WordPress/gutenberg/blob/317eb8f14c8e1b81bf56972cca2694be250580e3/packages/block-library/src/query-pagination-numbers/index.php#L22-L85
	 */
	const url = new URL( href );
	// When pretty permalinks are enabled, the page number may be in the path name.
	url.pathname = url.pathname.replace( /\/page\/[0-9]+/i, '' );
	// When plain permalinks are enabled, the page number may be in the "paged" query parameter.
	url.searchParams.delete( 'paged' );
	// On posts and pages the page number will be in a query parameter that
	// identifies which block we are paginating.
	url.searchParams.forEach( ( _, key ) => {
		if ( key.match( /^query(?:-[0-9]+)?-page$/ ) ) {
			url.searchParams.delete( key );
		}
	} );
	// Make sure to update the href with the changes.
	href = url.href;

	if ( needsRefresh || ( ! isBlockTheme && isProductArchive ) ) {
		return ( window.location.href = href );
	}
	return navigateFn( href, options );
}

export interface ProductFiltersContext {
	isDialogOpen: boolean;
	hasPageWithWordPressAdminBar: boolean;
	params: Record< string, string >;
	originalParams: Record< string, string >;
}

store( 'woocommerce/product-filters', {
	state: {
		isDialogOpen: () => {
			const context = getContext< ProductFiltersContext >();
			return context.isDialogOpen;
		},
	},
	actions: {
		openDialog: () => {
			const context = getContext< ProductFiltersContext >();
			document.body.classList.add( 'wc-modal--open' );
			context.hasPageWithWordPressAdminBar = Boolean(
				document.getElementById( 'wpadminbar' )
			);

			context.isDialogOpen = true;
		},
		closeDialog: () => {
			const context = getContext< ProductFiltersContext >();
			document.body.classList.remove( 'wc-modal--open' );

			context.isDialogOpen = false;
		},
	},
	callbacks: {
		maybeNavigate: () => {
			const { params, originalParams } =
				getContext< ProductFiltersContext >();

			if ( isParamsEqual( params, originalParams ) ) {
				return;
			}

			const url = new URL( window.location.href );
			const { searchParams } = url;

			for ( const key in originalParams ) {
				searchParams.delete( key, originalParams[ key ] );
			}

			for ( const key in params ) {
				searchParams.set( key, params[ key ] );
			}
			navigate( url.href );
		},
	},
} );
