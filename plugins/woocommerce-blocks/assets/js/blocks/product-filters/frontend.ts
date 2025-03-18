/**
 * External dependencies
 */
import * as iAPI from '@wordpress/interactivity';

const { getContext, store, getServerContext } = iAPI;

const getSetting = window.wc.wcSettings.getSetting;
const isBlockTheme = getSetting( 'isBlockTheme' );
const isProductArchive = getSetting( 'isProductArchive' );
const needsRefresh = getSetting( 'needsRefreshForInteractivityAPI', false );

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

async function navigate( href: string, options = {} ) {
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

	const { actions } = await import( '@wordpress/interactivity-router' );
	return actions.navigate( href, options );
}

export type ActiveFilter = {
	label: string;
	type: 'attribute' | 'price' | 'rating' | 'status';
	value: string | null;
	attribute?: {
		slug: string;
		queryType: 'and' | 'or';
	};
	price?: {
		min: number | null;
		max: number | null;
	};
};

export type ProductFiltersContext = {
	isOverlayOpened: boolean;
	params: Record< string, string >;
	originalParams: Record< string, string >;
	activeFilters: ActiveFilter[];
};

const productFiltersStore = store( 'woocommerce/product-filters', {
	state: {
		get params() {
			const { activeFilters } = getContext< ProductFiltersContext >();
			const params: Record< string, string > = {};

			function addParam( key: string, value: string ) {
				if ( key in params && params[ key ].length > 0 )
					return ( params[ key ] = `${ params[ key ] },${ value }` );
				params[ key ] = value;
			}

			activeFilters.forEach( ( filter ) => {
				const { type, value } = filter;

				if ( ! value ) return;

				if ( type === 'price' && 'price' in filter ) {
					if ( filter.price.min )
						params.min_price = filter.price.min.toString();
					if ( filter.price.max )
						params.max_price = filter.price.max.toString();
				}

				if ( type === 'status' ) {
					addParam( 'filter_stock_status', value );
				}

				if ( type === 'rating' ) {
					addParam( `rating_filter`, value );
				}

				if ( type === 'attribute' && 'attribute' in filter ) {
					addParam( `filter_${ filter.attribute.slug }`, value );
					params[ `query_type_${ filter.attribute.slug }` ] =
						filter.attribute.queryType;
				}
			} );
			return params;
		},
		get activeFilters() {
			const { activeFilters } = getContext< ProductFiltersContext >();
			return activeFilters
				.filter( ( item ) => !! item.value )
				.sort( ( a, b ) => {
					return a.label
						.toLowerCase()
						.localeCompare( b.label.toLowerCase() );
				} )
				.map( ( item ) => ( {
					...item,
					uid: `${ item.type }/${ item.value }`,
				} ) );
		},
	},
	actions: {
		openOverlay: () => {
			const context = getContext< ProductFiltersContext >();
			context.isOverlayOpened = true;
			if ( document.getElementById( 'wpadminbar' ) ) {
				const scrollTop = (
					document.documentElement ||
					document.body.parentNode ||
					document.body
				).scrollTop;
				document.body.style.setProperty(
					'--adminbar-mobile-padding',
					`max(calc(var(--wp-admin--admin-bar--height) - ${ scrollTop }px), 0px)`
				);
			}
		},
		closeOverlay: () => {
			const context = getContext< ProductFiltersContext >();
			context.isOverlayOpened = false;
		},
		closeOverlayOnEscape: ( event: KeyboardEvent ) => {
			const context = getContext< ProductFiltersContext >();
			if ( context.isOverlayOpened && event.key === 'Escape' ) {
				productFiltersStore.actions.closeOverlay();
			}
		},
		setActiveFilter: ( activeFilter: ActiveFilter ) => {
			const { value, type } = activeFilter;
			const context = getContext< ProductFiltersContext >();
			const newActiveFilters = context.activeFilters.filter(
				( item ) => ! ( item.value === value && item.type === type )
			);

			newActiveFilters.push( activeFilter );

			context.activeFilters = newActiveFilters;
		},
		removeActiveFiltersBy: (
			callback: ( item: ActiveFilter ) => boolean
		) => {
			const context = getContext< ProductFiltersContext >();
			context.activeFilters = context.activeFilters.filter(
				( item ) => ! callback( item )
			);
		},
		removeActiveFiltersByType: ( type: ActiveFilter[ 'type' ] ) => {
			productFiltersStore.actions.removeActiveFiltersBy(
				( item ) => item.type === type
			);
		},
		removeActiveFilter: (
			type: ActiveFilter[ 'type' ],
			value: ActiveFilter[ 'value' ]
		) => {
			productFiltersStore.actions.removeActiveFiltersBy(
				( item ) => item.type === type && item.value === value
			);
		},
		*navigate() {
			const { originalParams } = getServerContext
				? getServerContext< ProductFiltersContext >()
				: getContext< ProductFiltersContext >();

			if (
				isParamsEqual(
					productFiltersStore.state.params,
					originalParams
				)
			) {
				return;
			}

			const url = new URL( window.location.href );
			const { searchParams } = url;

			for ( const key in originalParams ) {
				searchParams.delete( key );
			}

			for ( const key in productFiltersStore.state.params ) {
				searchParams.set(
					key,
					productFiltersStore.state.params[ key ]
				);
			}

			yield navigate( url.href );
		},
	},
	callbacks: {
		scrollLimit: () => {
			const { isOverlayOpened } = getContext< ProductFiltersContext >();
			if ( isOverlayOpened ) {
				document.body.style.overflow = 'hidden';
			} else {
				document.body.style.overflow = 'auto';
			}
		},
	},
} );

export type ProductFiltersStore = typeof productFiltersStore;
