/**
 * External dependencies
 */
import * as iAPI from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { decodeHtmlEntities } from '../../utils/html-entities';
import type { SelectableItemsParentStore } from '../../types/type-defs/selectable-items';
import type {
	ActiveFilterItem,
	FilterItemFields,
	FilterOptionItem,
	ProductFiltersContext,
} from './types';
import { getClosestColor } from './utils/get-closest-color';
import { reload } from '../../utils/navigation';
import { PRODUCT_FILTERS_STORE_NAME } from './constants';

const { getContext, getElement, store, getServerContext, getConfig } = iAPI;

const BLOCK_NAME = PRODUCT_FILTERS_STORE_NAME;

type ValidFilterOptionItem = FilterOptionItem & {
	type: string;
	value: string;
};

function isValidFilterOptionItem(
	item: FilterOptionItem
): item is ValidFilterOptionItem {
	return (
		typeof item.type === 'string' &&
		item.type.length > 0 &&
		typeof item.value === 'string' &&
		item.value.length > 0
	);
}

function getFilterLabel( item: ValidFilterOptionItem ): string {
	const label = item.ariaLabel ?? item.label;
	return typeof label === 'string' && label.length > 0 ? label : item.value;
}

function selectFilter( item: ValidFilterOptionItem ) {
	const context = getContext< ProductFiltersContext >();
	const newActiveFilter: ActiveFilterItem = {
		value: item.value,
		type: item.type,
		activeLabel: context.activeLabelTemplate.replace(
			'{{label}}',
			getFilterLabel( item )
		),
	};
	if ( item.attributeQueryType ) {
		newActiveFilter.attributeQueryType = item.attributeQueryType;
	}
	const newActiveFilters = context.activeFilters.filter(
		( activeFilter ) =>
			! (
				activeFilter.value === newActiveFilter.value &&
				activeFilter.type === newActiveFilter.type
			)
	);

	newActiveFilters.push( newActiveFilter );

	context.activeFilters = newActiveFilters;
}

function unselectFilter( item: ValidFilterOptionItem ) {
	actions.removeActiveFiltersBy(
		( activeFilter ) =>
			activeFilter.type === item.type && activeFilter.value === item.value
	);
}

const productFiltersStore = {
	state: {
		get params() {
			const params: Record< string, string > = {};

			function addParam( key: string, value: string ) {
				if ( key in params && params[ key ].length > 0 )
					return ( params[ key ] = `${ params[ key ] },${ value }` );
				params[ key ] = value;
			}

			const config = getConfig( BLOCK_NAME );
			const taxonomyParamsMap = config?.taxonomyParamsMap || {};

			state.activeFilters.forEach( ( filter ) => {
				// todo: refactor this to use params data from Automattic\WooCommerce\Internal\ProductFilters\Params.
				const { type, value } = filter;

				if ( ! value ) return;

				if ( type === 'price' ) {
					const [ min, max ] = value.split( '|' );
					if ( min ) params.min_price = min;
					if ( max ) params.max_price = max;
				}

				if ( type === 'status' ) {
					addParam( 'filter_stock_status', value );
				}

				if ( type === 'rating' ) {
					addParam( `rating_filter`, value );
				}

				if ( type.includes( 'attribute' ) ) {
					const [ , slug ] = type.split( '/' );
					addParam( `filter_${ slug }`, value );
					params[ `query_type_${ slug }` ] =
						filter.attributeQueryType || 'or';
				}

				if ( type.includes( 'taxonomy' ) ) {
					const [ , taxonomy ] = type.split( '/' );
					const paramKey = taxonomyParamsMap[ taxonomy ];
					addParam( paramKey, value );
				}
			} );
			return params;
		},
		get activeFilters() {
			const { activeFilters } = getContext< ProductFiltersContext >();
			return activeFilters
				.filter( ( item ) => !! item.value )
				.sort( ( a, b ) => {
					return a.activeLabel
						.toLowerCase()
						.localeCompare( b.activeLabel.toLowerCase() );
				} )
				.map( ( item ) => ( {
					...item,
					activeLabel: decodeHtmlEntities( item.activeLabel ),
					uid: `${ item.type }/${ item.value }`,
				} ) );
		},
		get selectableItems() {
			// Items are server-owned (narrow on every navigation); read
			// from server context so they refresh post-navigation.
			// `getContext()` soft-merges and would keep the stale client
			// snapshot.
			const server = getServerContext
				? getServerContext< ProductFiltersContext >()
				: getContext< ProductFiltersContext >();
			const items = server.items;
			if ( ! Array.isArray( items ) ) return [];
			return items.map( ( item ) => ( {
				...item,
				selected: state.activeFilters.some(
					( filter ) =>
						filter.type === item.type && filter.value === item.value
				),
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
				actions.closeOverlay();
			}
		},
		removeActiveFiltersBy: (
			callback: ( item: ActiveFilterItem ) => boolean
		) => {
			const context = getContext< ProductFiltersContext >();
			context.activeFilters = context.activeFilters.filter(
				( item ) => ! callback( item )
			);
		},
		toggle: ( itemArg?: FilterOptionItem | Event ) => {
			const context = getContext< ProductFiltersContext >();
			const item =
				itemArg && ! ( itemArg instanceof Event )
					? itemArg
					: context.item;
			if ( ! item || ! isValidFilterOptionItem( item ) ) return;
			const isSelected = state.activeFilters.some(
				( f ) => f.type === item.type && f.value === item.value
			);
			if ( isSelected ) {
				unselectFilter( item );
			} else {
				selectFilter( item );
			}
			void actions.navigate();
		},
		*navigate() {
			const context = getServerContext
				? getServerContext< ProductFiltersContext >()
				: getContext< ProductFiltersContext >();

			const config = getConfig( BLOCK_NAME );
			const url = new URL( config.canonicalUrl );
			const { searchParams } = url;

			for ( const key in context.params ) {
				searchParams.delete( key );
			}

			for ( const key in state.params ) {
				const value = state.params[ key ];
				let decodedValue = value;

				try {
					decodedValue = decodeURIComponent( value );
				} catch ( error ) {
					if ( error instanceof URIError ) {
						// eslint-disable-next-line no-console
						console.warn(
							'woocommerce/product-filters: Failed to decode filter parameter',
							key,
							error
						);
					}
				}

				searchParams.set( key, decodedValue );
			}

			if ( window.location.href === url.href ) {
				return;
			}

			// Per-instance context (set when Product Filters is a descendant
			// of Product Collection) wins over the global config, which is
			// the fallback for the sibling-block layout.
			const forcePageReload =
				typeof context.forcePageReload === 'boolean'
					? context.forcePageReload
					: config?.forcePageReload;

			if ( forcePageReload ) {
				reload( url.href );
				return;
			}

			const routerModule: typeof import('@wordpress/interactivity-router') =
				yield import( '@wordpress/interactivity-router' );

			yield routerModule.actions.navigate( url.href );
		},
	},
	callbacks: {
		initColors: () => {
			const el = getElement();
			if ( ! el.ref ) return;

			const style = el.ref.style;
			const hasBg = style.getPropertyValue(
				'--wc-product-filters-background-color'
			);
			const hasFg = style.getPropertyValue(
				'--wc-product-filters-text-color'
			);

			if ( ! hasBg ) {
				const bg = getClosestColor( el.ref, 'backgroundColor' );
				if ( bg ) {
					style.setProperty(
						'--wc-product-filters-background-color',
						bg
					);
				}
			}
			if ( ! hasFg ) {
				const fg = getClosestColor( el.ref, 'color' );
				if ( fg ) {
					style.setProperty( '--wc-product-filters-text-color', fg );
				}
			}
		},
		scrollLimit: () => {
			const { isOverlayOpened } = getContext< ProductFiltersContext >();
			if ( isOverlayOpened ) {
				document.body.style.overflow = 'hidden';
			} else {
				document.body.style.overflow = 'auto';
			}
		},
		syncActiveFiltersWithServer: () => {
			if ( ! getServerContext ) return;
			const context = getContext< ProductFiltersContext >();
			const serverContext = getServerContext< ProductFiltersContext >();

			context.activeFilters = Array.isArray( serverContext.activeFilters )
				? serverContext.activeFilters.map( ( item ) => ( { ...item } ) )
				: [];
		},
	},
};

// Compile-time protocol conformance check.
// eslint-disable-next-line @typescript-eslint/no-unused-expressions
productFiltersStore satisfies SelectableItemsParentStore< FilterItemFields >;

export type ProductFiltersStore = typeof productFiltersStore;

const { state, actions } = store< ProductFiltersStore >(
	BLOCK_NAME,
	productFiltersStore
);
