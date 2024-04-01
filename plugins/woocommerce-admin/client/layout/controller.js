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
	getScreenFromPath,
	isWCAdmin,
} from '@woocommerce/navigation';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import getReports from '../analytics/report/get-reports';
import { getAdminSetting } from '~/utils/admin-settings';
import { isFeatureEnabled } from '~/utils/features';
import { NoMatch } from './NoMatch';

const AddProductPage = lazy( () =>
	import(
		/* webpackChunkName: "edit-product-page" */ '../products/add-product-page'
	)
);
const EditProductPage = lazy( () =>
	import(
		/* webpackChunkName: "edit-product-page" */ '../products/edit-product-page'
	)
);
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
const ProfileWizard = lazy( () =>
	import( /* webpackChunkName: "profile-wizard" */ '../profile-wizard' )
);

const CoreProfiler = lazy( () =>
	import( /* webpackChunkName: "core-profiler" */ '../core-profiler' )
);

const SettingsGroup = lazy( () =>
	import( /* webpackChunkName: "profile-wizard" */ '../settings' )
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

export const getPages = () => {
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
				const report = find( getReports(), {
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

	if ( isFeatureEnabled( 'marketplace' ) ) {
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
	}

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
	} else if (
		window.wcAdminFeatures[ 'new-product-management-experience' ]
	) {
		pages.push( {
			container: AddProductPage,
			path: '/add-product',
			breadcrumbs: [
				[ '/add-product', __( 'Product', 'woocommerce' ) ],
				__( 'Add New Product', 'woocommerce' ),
			],
			navArgs: {
				id: 'woocommerce-add-product',
			},
			wpOpenMenu: 'menu-posts-product',
			capability: 'manage_woocommerce',
		} );

		pages.push( {
			container: EditProductPage,
			path: '/product/:productId',
			breadcrumbs: [
				[ '/edit-product', __( 'Product', 'woocommerce' ) ],
				__( 'Edit Product', 'woocommerce' ),
			],
			navArgs: {
				id: 'woocommerce-edit-product',
			},
			wpOpenMenu: 'menu-posts-product',
			capability: 'manage_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures[ 'product-variation-management' ] ) {
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
	}

	if ( window.wcAdminFeatures.onboarding ) {
		if ( ! window.wcAdminFeatures[ 'core-profiler' ] ) {
			pages.push( {
				container: ProfileWizard,
				path: '/setup-wizard',
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Setup Wizard', 'woocommerce' ),
				],
				capability: 'manage_woocommerce',
			} );
		} else {
			pages.push( {
				container: CoreProfiler,
				path: '/setup-wizard',
				breadcrumbs: [
					...initialBreadcrumbs,
					__( 'Profiler', 'woocommerce' ),
				],
				capability: 'manage_woocommerce',
			} );
		}
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

	if ( window.wcAdminFeatures.settings ) {
		pages.push( {
			container: SettingsGroup,
			path: '/settings/:page',
			breadcrumbs: ( { match } ) => {
				// @todo This might need to be refactored to retrieve groups via data store.
				const settingsPages = getAdminSetting( 'settingsPages' );
				const page = settingsPages[ match.params.page ];
				if ( ! page ) {
					return [];
				}
				return [
					...initialBreadcrumbs,
					[
						settingsPages.general
							? '/settings/general'
							: `/settings/${
									Object.keys( settingsPages )[ 0 ]
							  }`,
						__( 'Settings', 'woocommerce' ),
					],
					page,
				];
			},
			wpOpenMenu: 'toplevel_page_woocommerce',
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
 * @param {HTMLElement} item            - Sidebar anchor link.
 * @param {Object}      nextQuery       - A query object to be added to updated hrefs.
 * @param {Array}       excludedScreens - wc-admin screens to avoid updating.
 */
export function updateLinkHref( item, nextQuery, excludedScreens ) {
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

		item.onclick = ( e ) => {
			e.preventDefault();
			getHistory().push( href );
		};
	}
}

// Update's wc-admin links in wp-admin menu
window.wpNavMenuUrlUpdate = function ( query ) {
	const nextQuery = getPersistedQuery( query );
	const excludedScreens = getQueryExcludedScreens();
	const anchors = document.querySelectorAll( '#adminmenu a' );

	Array.from( anchors ).forEach( ( item ) =>
		updateLinkHref( item, nextQuery, excludedScreens )
	);
};

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function ( page, url ) {
	const wpNavMenu = document.querySelector( '#adminmenu' );
	Array.from( wpNavMenu.getElementsByClassName( 'current' ) ).forEach(
		function ( item ) {
			item.classList.remove( 'current' );
		}
	);

	const submenu = Array.from(
		wpNavMenu.querySelectorAll( '.wp-has-current-submenu' )
	);
	submenu.forEach( function ( element ) {
		element.classList.remove( 'wp-has-current-submenu' );
		element.classList.remove( 'wp-menu-open' );
		element.classList.remove( 'selected' );
		element.classList.add( 'wp-not-current-submenu' );
		element.classList.add( 'menu-top' );
	} );

	const pageUrl =
		url === '/'
			? 'admin.php?page=wc-admin'
			: 'admin.php?page=wc-admin&path=' + encodeURIComponent( url );
	let currentItemsSelector =
		url === '/'
			? `li > a[href$="${ pageUrl }"], li > a[href*="${ pageUrl }?"]`
			: `li > a[href*="${ pageUrl }"]`;

	const parentPath = page.navArgs?.parentPath;
	if ( parentPath ) {
		const parentPageUrl =
			parentPath === '/'
				? 'admin.php?page=wc-admin'
				: 'admin.php?page=wc-admin&path=' +
				  encodeURIComponent( parentPath );
		currentItemsSelector += `, li > a[href*="${ parentPageUrl }"]`;
	}

	const currentItems = wpNavMenu.querySelectorAll( currentItemsSelector );

	Array.from( currentItems ).forEach( function ( item ) {
		item.parentElement.classList.add( 'current' );
	} );

	if ( page.wpOpenMenu ) {
		const currentMenu = wpNavMenu.querySelector( '#' + page.wpOpenMenu );
		if ( currentMenu ) {
			currentMenu.classList.remove( 'wp-not-current-submenu' );
			currentMenu.classList.add( 'wp-has-current-submenu' );
			currentMenu.classList.add( 'wp-menu-open' );
			currentMenu.classList.add( 'current' );
		}
	}

	const wpWrap = document.querySelector( '#wpwrap' );
	wpWrap.classList.remove( 'wp-responsive-open' );
};
