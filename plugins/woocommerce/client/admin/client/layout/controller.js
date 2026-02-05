/**
 * External dependencies
 */
import { Suspense, lazy } from '@wordpress/element';
import { useRef, useEffect } from 'react';
import { parse, stringify } from 'qs';
import { find, isEqual, last, omit } from 'lodash';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import {
	getNewPath,
	getPersistedQuery,
	getHistory,
	getQueryExcludedScreens,
	getQueryExcludedScreensUrlUpdate,
	getScreenFromPath,
	isWCAdmin,
} from '@woocommerce/navigation';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useReports } from '../analytics/report/use-reports';
import { getAdminSetting } from '~/utils/admin-settings';
import { isFeatureEnabled } from '~/utils/features';
import { useFilterHook } from '~/utils/use-filter-hook';
import { NoMatch } from './NoMatch';

const ProductVariationPage = lazy( () =>
	import(
		/* webpackChunkName: "edit-product-page" */ '../products/product-variation-page'
	)
);
const ProductPage = lazy( () =>
	import( /* webpackChunkName: "product-page" */ '../products/product-page' )
);
const AnalyticsReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report" */ '../analytics/report' )
);
const AnalyticsSettings = lazy( () =>
	import(
		/* webpackChunkName: "analytics-settings" */ '../analytics/settings'
	)
);
const Dashboard = lazy( () =>
	import( /* webpackChunkName: "dashboard" */ '../dashboard' )
);
const Homescreen = lazy( () =>
	import( /* webpackChunkName: "homescreen" */ '../homescreen' )
);
const MarketingOverviewMultichannel = lazy( () =>
	import(
		/* webpackChunkName: "multichannel-marketing" */ '../marketing/overview-multichannel'
	)
);
const Marketplace = lazy( () =>
	import( /* webpackChunkName: "marketplace" */ '../marketplace' )
);

const CoreProfiler = lazy( () =>
	import( /* webpackChunkName: "core-profiler" */ '../core-profiler' )
);

const WCPaymentsWelcomePage = lazy( () =>
	import(
		/* webpackChunkName: "wcpay-payment-welcome-page" */ '../payments-welcome'
	)
);

const CustomizeStore = lazy( () =>
	import( /* webpackChunkName: "customize-store" */ '../customize-store' )
);

const LaunchStore = lazy( () =>
	import( /* webpackChunkName: "launch-store" */ '../launch-your-store/hub' )
);

export const PAGES_FILTER = 'woocommerce_admin_pages_list';

export const getPages = ( reports = [] ) => {
	const pages = [];
	const initialBreadcrumbs = [
		[ '', getAdminSetting( 'woocommerceTranslation' ) ],
	];

	pages.push( {
		container: Homescreen,
		path: '/',
		breadcrumbs: [ ...initialBreadcrumbs, __( 'Home', 'woocommerce' ) ],
		wpOpenMenu: 'toplevel_page_woocommerce',
		navArgs: {
			id: 'woocommerce-home',
		},
		capability: 'manage_woocommerce',
	} );

	if ( window.wcAdminFeatures.analytics ) {
		pages.push( {
			container: Dashboard,
			path: '/analytics/overview',
			breadcrumbs: [
				...initialBreadcrumbs,
				[ '/analytics/overview', __( 'Analytics', 'woocommerce' ) ],
				__( 'Overview', 'woocommerce' ),
			],
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
			navArgs: {
				id: 'woocommerce-analytics-overview',
			},
			capability: 'view_woocommerce_reports',
		} );
		pages.push( {
			container: AnalyticsSettings,
			path: '/analytics/settings',
			breadcrumbs: [
				...initialBreadcrumbs,
				[ '/analytics/revenue', __( 'Analytics', 'woocommerce' ) ],
				__( 'Settings', 'woocommerce' ),
			],
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
			navArgs: {
				id: 'woocommerce-analytics-settings',
			},
			capability: 'view_woocommerce_reports',
		} );
		pages.push( {
			container: AnalyticsReport,
			path: '/customers',
			breadcrumbs: [
				...initialBreadcrumbs,
				__( 'Customers', 'woocommerce' ),
			],
			wpOpenMenu: 'toplevel_page_woocommerce',
			navArgs: {
				id: 'woocommerce-analytics-customers',
			},
			capability: 'view_woocommerce_reports',
		} );
		pages.push( {
			container: AnalyticsReport,
			path: '/analytics/:report',
			breadcrumbs: ( { match } ) => {
				const report = find( reports, {
					report: match.params.report,
				} );
				if ( ! report ) {
					return [];
				}
				return [
					...initialBreadcrumbs,
					[ '/analytics/revenue', __( 'Analytics', 'woocommerce' ) ],
					report.title,
				];
			},
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
			capability: 'view_woocommerce_reports',
		} );
	}

	if ( window.wcAdminFeatures.marketing ) {
		pages.push( {
			container: MarketingOverviewMultichannel,
			path: '/marketing',
			breadcrumbs: [
				...initialBreadcrumbs,
				[ '/marketing', __( 'Marketing', 'woocommerce' ) ],
				__( 'Overview', 'woocommerce' ),
			],
			wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			navArgs: {
				id: 'woocommerce-marketing-overview',
			},
			capability: 'view_woocommerce_reports',
		} );
	}

	pages.push( {
		container: Marketplace,
		layout: {
			header: false,
		},
		path: '/extensions',
		breadcrumbs: [
			[ '/extensions', __( 'Extensions', 'woocommerce' ) ],
			__( 'Extensions', 'woocommerce' ),
		],
		wpOpenMenu: 'toplevel_page_woocommerce',
		capability: 'manage_woocommerce',
		navArgs: {
			id: 'woocommerce-marketplace',
		},
	} );

	if ( isFeatureEnabled( 'product_block_editor' ) ) {
		const productPage = {
			container: ProductPage,
			layout: {
				header: false,
			},
			wpOpenMenu: 'menu-posts-product',
			capability: 'manage_woocommerce',
		};

		pages.push( {
			...productPage,
			path: '/add-product',
			breadcrumbs: [
				[ '/add-product', __( 'Product', 'woocommerce' ) ],
				__( 'Add New Product', 'woocommerce' ),
			],
			navArgs: {
				id: 'woocommerce-add-product',
			},
		} );

		pages.push( {
			...productPage,
			path: '/product/:productId',
			breadcrumbs: [
				[ '/edit-product', __( 'Product', 'woocommerce' ) ],
				__( 'Edit Product', 'woocommerce' ),
			],
			navArgs: {
				id: 'woocommerce-edit-product',
			},
		} );
	}

	pages.push( {
		container: ProductVariationPage,
		layout: {
			header: false,
		},
		path: '/product/:productId/variation/:variationId',
		breadcrumbs: [
			[ '/edit-product', __( 'Product', 'woocommerce' ) ],
			__( 'Edit Product Variation', 'woocommerce' ),
		],
		navArgs: {
			id: 'woocommerce-edit-product',
		},
		wpOpenMenu: 'menu-posts-product',
		capability: 'edit_products',
	} );

	if ( window.wcAdminFeatures.onboarding ) {
		pages.push( {
			container: CoreProfiler,
			path: '/setup-wizard',
			breadcrumbs: [
				...initialBreadcrumbs,
				__( 'Profiler', 'woocommerce' ),
			],
			capability: 'manage_woocommerce',
			layout: {
				header: false,
				footer: false,
				showNotices: true,
				showStoreAlerts: false,
				showPluginArea: false,
			},
		} );
	}

	if ( window.wcAdminFeatures[ 'core-profiler' ] ) {
		pages.push( {
			container: CoreProfiler,
			path: '/profiler',
			breadcrumbs: [
				...initialBreadcrumbs,
				__( 'Profiler', 'woocommerce' ),
			],
			capability: 'manage_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures[ 'customize-store' ] ) {
		pages.push( {
			container: CustomizeStore,
			path: '/customize-store/*',
			breadcrumbs: [
				...initialBreadcrumbs,
				__( 'Customize Your Store', 'woocommerce' ),
			],
			layout: {
				header: false,
				footer: true,
				showNotices: true,
				showStoreAlerts: false,
				showPluginArea: false,
			},
			capability: 'manage_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures[ 'launch-your-store' ] ) {
		pages.push( {
			container: LaunchStore,
			path: '/launch-your-store/*',
			breadcrumbs: [
				...initialBreadcrumbs,
				__( 'Launch Your Store', 'woocommerce' ),
			],
			layout: {
				header: false,
				footer: true,
				showNotices: true,
				showStoreAlerts: false,
				showPluginArea: false,
			},
			capability: 'manage_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures[ 'wc-pay-welcome-page' ] ) {
		pages.push( {
			container: WCPaymentsWelcomePage,
			path: '/wc-pay-welcome-page',
			breadcrumbs: [
				[ '/wc-pay-welcome-page', __( 'WooPayments', 'woocommerce' ) ],
				__( 'WooPayments', 'woocommerce' ),
			],
			navArgs: {
				id: 'woocommerce-wc-pay-welcome-page',
			},
			wpOpenMenu: 'toplevel_page_woocommerce-wc-pay-welcome-page',
			capability: 'manage_woocommerce',
		} );
	}

	/**
	 * List of WooCommerce Admin pages.
	 *
	 * @filter woocommerce_admin_pages_list
	 * @param {Array.<Object>} pages Array page objects.
	 */
	const filteredPages = applyFilters( PAGES_FILTER, pages );

	filteredPages.push( {
		container: NoMatch,
		path: '*',
		breadcrumbs: [
			...initialBreadcrumbs,
			__( 'Not allowed', 'woocommerce' ),
		],
		wpOpenMenu: 'toplevel_page_woocommerce',
	} );

	return filteredPages;
};

export function usePages() {
	const reports = useReports();
	return useFilterHook( PAGES_FILTER, () => getPages( reports ), [
		reports,
	] );
}

function usePrevious( value ) {
	const ref = useRef();
	useEffect( () => {
		ref.current = value;
	}, [ value ] );
	return ref.current;
}

export const Controller = ( { ...props } ) => {
	const prevProps = usePrevious( props );

	useEffect( () => {
		window.document.documentElement.scrollTop = 0;
		window.document.body.classList.remove( 'woocommerce-admin-is-loading' );
	}, [] );

	useEffect( () => {
		if ( prevProps ) {
			const prevBaseQuery = omit(
				prevProps.query,
				'chartType',
				'filter',
				'paged'
			);
			const baseQuery = omit(
				props.query,
				'chartType',
				'filter',
				'paged'
			);

			if (
				prevProps.query.paged > 1 &&
				! isEqual( prevBaseQuery, baseQuery )
			) {
				getHistory().replace( getNewPath( { paged: 1 } ) );
			}

			if ( prevProps.match.url !== props.match.url ) {
				window.document.documentElement.scrollTop = 0;
			}
		}
	}, [ props, prevProps ] );

	const { page, match, query } = props;
	const { url, params } = match;

	window.wpNavMenuUrlUpdate( query );
	window.wpNavMenuClassChange( page, url );

	function getFallback() {
		return page.fallback ? (
			<page.fallback />
		) : (
			<div className="woocommerce-layout__loading">
				<Spinner />
			</div>
		);
	}

	return (
		<Suspense fallback={ getFallback() }>
			<page.container
				params={ params }
				path={ url }
				pathMatch={ page.path }
				query={ query }
			/>
		</Suspense>
	);
};

/**
 * Update an anchor's link in sidebar to include persisted queries. Leave excluded screens
 * as is.
 *
 * @param {HTMLElement} item                     - Sidebar anchor link.
 * @param {Object}      nextQuery                - A query object to be added to updated hrefs.
 * @param {Array}       excludedScreens          - wc-admin screens to avoid updating.
 * @param {Array}       excludedScreensUrlUpdate - wc-admin screens to avoid updating URL.
 */
export function updateLinkHref(
	item,
	nextQuery,
	excludedScreens,
	excludedScreensUrlUpdate = []
) {
	if ( isWCAdmin( item.href ) ) {
		const search = last( item.href.split( '?' ) );
		const query = parse( search );
		const path = query.path || 'homescreen';
		const screen = getScreenFromPath( path );

		const isExcludedScreen = excludedScreens.includes( screen );

		const href =
			'admin.php?' +
			stringify(
				Object.assign( query, isExcludedScreen ? {} : nextQuery )
			);

		// Replace the href so you can see the url on hover.
		item.href = href;

		const isExcludedScreenUrlUpdate =
			excludedScreensUrlUpdate.includes( screen );

		if ( ! isExcludedScreenUrlUpdate ) {
			item.onclick = ( e ) => {
				if ( e.ctrlKey || e.metaKey ) {
					return;
				}

				e.preventDefault();
				getHistory().push( href );
			};
		}
	}
}

// Update's wc-admin links in wp-admin menu
window.wpNavMenuUrlUpdate = function ( query ) {
	const nextQuery = getPersistedQuery( query );
	const excludedScreens = getQueryExcludedScreens();
	const excludedScreensUrlUpdate = getQueryExcludedScreensUrlUpdate();
	const anchors = document.querySelectorAll( '#adminmenu a' );

	Array.from( anchors ).forEach( ( item ) =>
		updateLinkHref(
			item,
			nextQuery,
			excludedScreens,
			excludedScreensUrlUpdate
		)
	);
};

// Cache custom SVG menu items to prevent repeated DOM queries. This is necessary to remove event handlers when the route changes in window.wpNavMenuClassChange.
const customSVGMenuItems = [];
const getCustomSVGMenuItems = () => {
	// Get all menu items with SVG backgrounds
	// See: https://github.com/WordPress/wordpress-develop/blob/22bebd7de6681c673933953aab8c08802e7e3d4a/src/js/_enqueues/wp/svg-painter.js#L140-L148
	const menuItems = window.jQuery(
		'#adminmenu .wp-menu-image, #wpadminbar .ab-item'
	);
	menuItems.each( function () {
		const $this = window.jQuery( this ),
			bgImage = $this.css( 'background-image' );

		if (
			bgImage &&
			bgImage.indexOf( 'data:image/svg+xml;base64' ) !== -1
		) {
			customSVGMenuItems.push( $this.parent().parent() );
		}
	} );
};

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function ( page, url ) {
	if ( customSVGMenuItems.length === 0 ) {
		getCustomSVGMenuItems();
	}

	const wpNavMenu = document.querySelector( '#adminmenu' );

	// 1. Remove all current states
	const currentItems = Array.from(
		wpNavMenu.getElementsByClassName( 'current' )
	);
	currentItems.forEach( ( item ) => {
		item.classList.remove( 'current' );
	} );

	const submenuItems = Array.from(
		wpNavMenu.querySelectorAll( '.wp-has-current-submenu' )
	);
	submenuItems.forEach( function ( element ) {
		element.classList.remove(
			'wp-has-current-submenu',
			'selected',
			'wp-menu-open'
		);
		element.classList.add( 'wp-not-current-submenu' );
	} );

	// 2. Get current page URL and item selector
	const pageUrl =
		url === '/'
			? 'admin.php?page=wc-admin'
			: 'admin.php?page=wc-admin&path=' + encodeURIComponent( url );

	let currentItemsSelector =
		url === '/'
			? `li > a[href$="${ pageUrl }"], li > a[href*="${ pageUrl }?"]`
			: `li > a[href*="${ pageUrl }"]`;

	// 3. Handle parent paths with proper hierarchy
	const parentPath = page.navArgs?.parentPath;
	if ( parentPath ) {
		const parentPageUrl =
			parentPath === '/'
				? 'admin.php?page=wc-admin'
				: 'admin.php?page=wc-admin&path=' +
				  encodeURIComponent( parentPath );
		currentItemsSelector += `, li > a[href*="${ parentPageUrl }"]`;
	}

	// 4. Set current menu item to active
	const newCurrentItems = Array.from(
		wpNavMenu.querySelectorAll( currentItemsSelector )
	);
	newCurrentItems.forEach( ( item ) => {
		item.parentElement.classList.add( 'current' );
	} );

	// 5. Handle explicit menu opening
	if ( page.wpOpenMenu ) {
		const currentMenu = wpNavMenu.querySelector( `#${ page.wpOpenMenu }` );
		if ( currentMenu ) {
			// Reset the margin-top immediately so menu can open smoothly without jumping
			const allSubmenus = wpNavMenu.querySelectorAll( '.wp-submenu' );
			allSubmenus.forEach( ( submenu ) => {
				submenu.style.marginTop = ''; // Reset margin-top
			} );

			currentMenu.classList.remove( 'wp-not-current-submenu' );
			currentMenu.classList.add(
				'wp-has-current-submenu',
				'wp-menu-open',
				'current'
			);
		}
	}

	// 6. Attempt to re-color SVG icons used in the admin menu or the toolbar
	if ( window.wp && window.wp.svgPainter ) {
		// Detach SVG painting event handlers from menu items to prevent the active state from being reset on hover. For more information, see: https://github.com/WordPress/wordpress-develop/blob/22bebd7de6681c673933953aab8c08802e7e3d4a/src/js/_enqueues/wp/svg-painter.js#L162C4-L170C10
		customSVGMenuItems.forEach( ( $menuItem ) => {
			const events =
				window.jQuery._data( $menuItem[ 0 ], 'events' ) || {};

			if ( events.mouseover ) {
				events.mouseover.forEach( ( event ) => {
					if ( event.handler.toString().includes( 'paintElement' ) ) {
						$menuItem.off( 'mouseenter', event.handler );
					}
				} );
			}

			if ( events.mouseout ) {
				events.mouseout.forEach( ( event ) => {
					if ( event.handler.toString().includes( 'paintElement' ) ) {
						$menuItem.off( 'mouseleave', event.handler );
					}
				} );
			}
		} );

		window.wp.svgPainter.paint();
	}

	// 7. Close responsive menu if open
	const wpWrap = document.querySelector( '#wpwrap' );
	if ( wpWrap && wpWrap.classList.contains( 'wp-responsive-open' ) ) {
		wpWrap.classList.remove( 'wp-responsive-open' );
	}
};
