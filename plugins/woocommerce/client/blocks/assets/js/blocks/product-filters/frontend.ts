/**
 * External dependencies
 */
import * as iAPI from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { decodeHtmlEntities } from '../../utils/html-entities';

const { getContext, store, getServerContext, getConfig } = iAPI;

const BLOCK_NAME = 'woocommerce/product-filters';

function selectFilter() {
	const context = getContext< ProductFiltersContext >();
	const newActiveFilter = {
		value: context.item.value,
		type: context.item.type,
		attributeQueryType: context.item.attributeQueryType,
		activeLabel: context.activeLabelTemplate.replace(
			'{{label}}',
			context.item?.ariaLabel || context.item.label
		),
	};
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

function unselectFilter() {
	const { item } = getContext< ProductFiltersContext >();
	actions.removeActiveFiltersBy(
		( activeFilter ) =>
			activeFilter.type === item.type && activeFilter.value === item.value
	);
}

type FilterItem = {
	label: string;
	ariaLabel?: string;
	value: string;
	selected: boolean;
	count: number;
	type: string;
	attributeQueryType?: 'and' | 'or' | undefined;
	id?: number;
	parent?: number;
	depth?: number;
};

export type ActiveFilterItem = Pick<
	FilterItem,
	'type' | 'value' | 'attributeQueryType'
> & {
	activeLabel: string;
};

export type ProductFiltersContext = {
	isOverlayOpened: boolean;
	params: Record< string, string >;
	activeFilters: ActiveFilterItem[];
	item: FilterItem;
	activeLabelTemplate: string;
	filterType: string;
};

const productFiltersStore = {
	state: {
		get params() {
			const { activeFilters } = getContext< ProductFiltersContext >();
			const params: Record< string, string > = {};

			function addParam( key: string, value: string ) {
				if ( key in params && params[ key ].length > 0 )
					return ( params[ key ] = `${ params[ key ] },${ value }` );
				params[ key ] = value;
			}

			const config = getConfig( BLOCK_NAME );
			const taxonomyParamsMap = config?.taxonomyParamsMap || {};

			activeFilters.forEach( ( filter ) => {
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
		get isFilterSelected() {
			const { activeFilters, item } =
				getContext< ProductFiltersContext >();
			return activeFilters.some(
				( filter ) =>
					filter.type === item.type && filter.value === item.value
			);
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
		toggleFilter: () => {
			if ( state.isFilterSelected ) {
				unselectFilter();
			} else {
				selectFilter();
			}
			actions.navigate();
		},
		// TODO: Remove the hardcoded type once https://github.com/woocommerce/gutenberg/pull/8 is merged.
		*navigate(): Generator {
			const context = getServerContext
				? getServerContext< ProductFiltersContext >()
				: getContext< ProductFiltersContext >();

			const canonicalUrl = getConfig( BLOCK_NAME ).canonicalUrl;
			const url = new URL( canonicalUrl );
			const { searchParams } = url;

			for ( const key in context.params ) {
				searchParams.delete( key );
			}

			for ( const key in state.params ) {
				searchParams.set( key, state.params[ key ] );
			}

			if ( window.location.href === url.href ) {
				return;
			}

			const sharedSettings = getConfig( 'woocommerce' );
			const productFilterSettings = getConfig( BLOCK_NAME );
			const isBlockTheme = sharedSettings?.isBlockTheme || false;
			const isProductArchive =
				productFilterSettings?.isProductArchive || false;
			const needsRefreshForInteractivityAPI =
				sharedSettings?.needsRefreshForInteractivityAPI || false;

			if (
				needsRefreshForInteractivityAPI ||
				( ! isBlockTheme && isProductArchive )
			) {
				return ( window.location.href = url.href );
			}

			const routerModule: typeof import('@wordpress/interactivity-router') =
				yield import( '@wordpress/interactivity-router' );

			yield routerModule.actions.navigate( url.href );
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
};

export type ProductFiltersStore = typeof productFiltersStore;

const { state, actions } = store< ProductFiltersStore >(
	BLOCK_NAME,
	productFiltersStore
);
