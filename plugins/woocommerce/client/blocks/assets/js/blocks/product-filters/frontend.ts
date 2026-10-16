/**
 * External dependencies
 */
import * as iAPI from '@wordpress/interactivity';
import type { SelectableItemsParentStore } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { decodeHtmlEntities } from '../../utils/html-entities';
import type {
	ActiveFilterItem,
	FilterItemFields,
	FilterOptionItem,
	ProductFiltersContext,
} from './types';
import { getClosestColor } from './utils/get-closest-color';
import { reload } from '../../utils/navigation';
import { PRODUCT_FILTERS_STORE_NAME } from './constants';
import {
	getTabbableElements,
	isTopmostOverlay,
	setOverlayScrolling,
} from './utils/overlay';

const {
	getContext,
	getElement,
	store,
	getServerContext,
	getConfig,
	withSyncEvent,
} = iAPI;

const BLOCK_NAME = PRODUCT_FILTERS_STORE_NAME;

const overlayOpenState = new WeakMap< HTMLElement, boolean >();

function getFiltersElement() {
	return getElement().ref?.closest< HTMLElement >(
		'.wc-block-product-filters'
	);
}

function getOverlayDialog( element: HTMLElement ) {
	return element.querySelector< HTMLElement >(
		'.wc-block-product-filters__overlay-dialog'
	);
}

function focusOverlay( element: HTMLElement ) {
	const dialog = getOverlayDialog( element );
	if ( dialog ) {
		( getTabbableElements( dialog )[ 0 ] || dialog ).focus();
	}
}

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
		get isOverlayOpen() {
			const context = getContext< ProductFiltersContext >();
			return Boolean(
				context.isOverlayActive && context.isOverlayOpened
			);
		},
		get isOverlayHidden() {
			const context = getContext< ProductFiltersContext >();
			return Boolean(
				context.isOverlayActive && ! context.isOverlayOpened
			);
		},
		get overlayRole() {
			return state.isOverlayOpen ? 'dialog' : null;
		},
		get overlayAriaModal() {
			return state.isOverlayOpen ? 'true' : null;
		},
		get params() {
			const params: Record< string, string > = {};

			function addParam( key: string, value: string ) {
				if ( key in params && params[ key ].length > 0 ) {
					return ( params[ key ] = `${ params[ key ] },${ value }` );
				}
				params[ key ] = value;
			}

			const config = getConfig( BLOCK_NAME );
			const taxonomyParamsMap = config?.taxonomyParamsMap || {};

			state.activeFilters.forEach( ( filter ) => {
				// todo: refactor this to use params data from Automattic\WooCommerce\Internal\ProductFilters\Params.
				const { type, value } = filter;

				if ( ! value ) {
					return;
				}

				if ( type === 'price' ) {
					const [ min, max ] = value.split( '|' );
					if ( min ) {
						params.min_price = min;
					}
					if ( max ) {
						params.max_price = max;
					}
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
			if ( ! Array.isArray( items ) ) {
				return [];
			}
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
		closeOverlayOnBackdrop: withSyncEvent( ( event: MouseEvent ) => {
			if ( event.target === event.currentTarget ) {
				actions.closeOverlay();
			}
		} ),
		closeOverlayOnEscape: ( event: KeyboardEvent ) => {
			const context = getContext< ProductFiltersContext >();
			if (
				context.isOverlayOpened &&
				event.key === 'Escape' &&
				! event.defaultPrevented
			) {
				actions.closeOverlay();
			}
		},
		handleOverlayKeydown: withSyncEvent( ( event: KeyboardEvent ) => {
			const element = getFiltersElement();
			if (
				! element ||
				! state.isOverlayOpen ||
				! isTopmostOverlay( element ) ||
				event.defaultPrevented
			) {
				return;
			}
			if ( event.key === 'Escape' ) {
				actions.closeOverlayOnEscape( event );
				event.preventDefault();
			} else if ( event.key === 'Tab' ) {
				const dialog = getOverlayDialog( element );
				if ( ! dialog ) {
					return;
				}
				const tabbable = getTabbableElements( dialog );
				const first = tabbable[ 0 ];
				const last = tabbable[ tabbable.length - 1 ];
				const active = element.ownerDocument.activeElement;
				if (
					! first ||
					! dialog.contains( active ) ||
					active === dialog ||
					( event.shiftKey ? active === first : active === last )
				) {
					event.preventDefault();
					( ( event.shiftKey ? last : first ) || dialog ).focus();
				}
			}
		} ),
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
			if ( ! item || ! isValidFilterOptionItem( item ) ) {
				return;
			}
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

			// Capture the root before navigation can remove the originating control.
			const element = getFiltersElement();
			const routerModule: typeof import( '@wordpress/interactivity-router' ) =
				yield import( '@wordpress/interactivity-router' );

			yield routerModule.actions.navigate( url.href );

			const focused = element?.ownerDocument.activeElement ?? null;
			if (
				state.isOverlayOpen &&
				element?.isConnected &&
				( ! getOverlayDialog( element )?.contains( focused ) ||
					! ( focused instanceof HTMLElement ) ||
					! focused.getClientRects().length ||
					getComputedStyle( focused ).visibility !== 'visible' )
			) {
				focusOverlay( element );
			}
		},
	},
	callbacks: {
		initOverlay: () => {
			callbacks.syncOverlayMode();
			const element = getFiltersElement();
			return () => {
				if ( element ) {
					setOverlayScrolling( element, false );
					overlayOpenState.delete( element );
				}
			};
		},
		syncOverlayMode: () => {
			const element = getFiltersElement();
			const overlay = element?.querySelector< HTMLElement >(
				'.wc-block-product-filters__overlay'
			);
			if ( ! element || ! overlay ) {
				return;
			}
			const context = getContext< ProductFiltersContext >();
			const wasActive = context.isOverlayActive;
			// The theme's responsive styles determine the breakpoint, including custom viewport settings.
			context.isOverlayActive =
				getComputedStyle( overlay ).position === 'fixed';
			const opener = element.querySelector< HTMLButtonElement >(
				'.wc-block-product-filters__open-overlay'
			);
			if ( ! context.isOverlayActive ) {
				const wasOpen = context.isOverlayOpened;
				const focused = element.ownerDocument.activeElement;
				context.isOverlayOpened = false;
				overlay.inert = false;
				const focusIsVisible =
					focused instanceof HTMLElement &&
					focused.getClientRects().length > 0 &&
					getComputedStyle( focused ).visibility === 'visible';
				if (
					wasActive &&
					( focused === opener ||
						( overlay.contains( focused ) && ! focusIsVisible ) ||
						( wasOpen && focused === document.body ) )
				) {
					focusOverlay( element );
				}
			} else if (
				wasActive === false &&
				! context.isOverlayOpened &&
				overlay.contains( element.ownerDocument.activeElement )
			) {
				opener?.focus();
			}
		},
		manageOverlayFocus: () => {
			const element = getFiltersElement();
			if ( ! element ) {
				return;
			}
			const isOpen = state.isOverlayOpen;
			const wasOpen = overlayOpenState.get( element );
			overlayOpenState.set( element, isOpen );
			if ( isOpen && ! wasOpen ) {
				focusOverlay( element );
			} else if ( wasOpen && ! isOpen ) {
				const context = getContext< ProductFiltersContext >();
				if ( context.isOverlayActive ) {
					element
						.querySelector< HTMLButtonElement >(
							'.wc-block-product-filters__open-overlay'
						)
						?.focus();
				}
			}
		},
		initColors: () => {
			const el = getElement();
			if ( ! el.ref ) {
				return;
			}

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
			const element = getFiltersElement();
			if ( element ) {
				setOverlayScrolling( element, state.isOverlayOpen );
			}
		},
		syncActiveFiltersWithServer: () => {
			if ( ! getServerContext ) {
				return;
			}
			const context = getContext< ProductFiltersContext >();
			const serverContext = getServerContext< ProductFiltersContext >();

			context.activeFilters = Array.isArray( serverContext.activeFilters )
				? serverContext.activeFilters.map( ( item ) => ( { ...item } ) )
				: [];
		},
	},
};

// Compile-time protocol conformance check.
productFiltersStore satisfies SelectableItemsParentStore< FilterItemFields >;

export type ProductFiltersStore = typeof productFiltersStore;

const { state, actions, callbacks } = store< ProductFiltersStore >(
	BLOCK_NAME,
	productFiltersStore
);
