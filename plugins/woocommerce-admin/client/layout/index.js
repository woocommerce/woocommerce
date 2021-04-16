/**
 * External dependencies
 */
import { SlotFillProvider } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Component, lazy, Suspense } from '@wordpress/element';
import { Router, Route, Switch } from 'react-router-dom';
import PropTypes from 'prop-types';
import { get, isFunction, identity } from 'lodash';
import { parse } from 'qs';
import { Spinner } from '@woocommerce/components';
import { getHistory, getQuery } from '@woocommerce/navigation';
import { getSetting } from '@woocommerce/wc-admin-settings';
import {
	PLUGINS_STORE_NAME,
	useUser,
	withPluginsHydration,
	withOptionsHydration,
} from '@woocommerce/data';
import { recordPageView } from '@woocommerce/tracks';
import '@woocommerce/notices';

/**
 * Internal dependencies
 */
import './style.scss';
import { Controller, getPages } from './controller';
import { Header } from '../header';
import Notices from './notices';
import TransientNotices from './transient-notices';
import './navigation';

const StoreAlerts = lazy( () =>
	import( /* webpackChunkName: "store-alerts" */ './store-alerts' )
);

const WCPayUsageModal = lazy( () =>
	import(
		/* webpackChunkName: "wcpay-usage-modal" */ '../task-list/tasks/payments/methods/wcpay/wcpay-usage-modal'
	)
);

export class PrimaryLayout extends Component {
	render() {
		const { children } = this.props;
		return (
			<div
				className="woocommerce-layout__primary"
				id="woocommerce-layout__primary"
			>
				{ window.wcAdminFeatures[ 'store-alerts' ] && (
					<Suspense fallback={ <Spinner /> }>
						<StoreAlerts />
					</Suspense>
				) }
				<Notices />
				{ children }
			</div>
		);
	}
}

class _Layout extends Component {
	componentDidMount() {
		this.recordPageViewTrack();
	}

	componentDidUpdate( prevProps ) {
		const previousPath = get( prevProps, 'location.pathname' );
		const currentPath = get( this.props, 'location.pathname' );

		if ( ! previousPath || ! currentPath ) {
			return;
		}

		if ( previousPath !== currentPath ) {
			this.recordPageViewTrack();
		}
	}

	recordPageViewTrack() {
		const {
			activePlugins,
			installedPlugins,
			isEmbedded,
			isJetpackConnected,
		} = this.props;

		const navigationFlag = {
			has_navigation: !! window.wcNavigation,
		};

		if ( isEmbedded ) {
			const path = document.location.pathname + document.location.search;
			recordPageView( path, {
				is_embedded: true,
				...navigationFlag,
			} );
			return;
		}

		const pathname = get( this.props, 'location.pathname' );
		if ( ! pathname ) {
			return;
		}

		// Remove leading slash, and camel case remaining pathname
		let path = pathname.substring( 1 ).replace( /\//g, '_' );

		// When pathname is `/` we are on the home screen.
		if ( path.length === 0 ) {
			path = 'home_screen';
		}

		recordPageView( path, {
			jetpack_installed: installedPlugins.includes( 'jetpack' ),
			jetpack_active: activePlugins.includes( 'jetpack' ),
			jetpack_connected: isJetpackConnected,
			...navigationFlag,
		} );
	}

	getQuery( searchString ) {
		if ( ! searchString ) {
			return {};
		}

		const search = searchString.substring( 1 );
		return parse( search );
	}

	isWCPaySettingsPage() {
		const { page, section, tab } = getQuery();
		return (
			page === 'wc-settings' &&
			tab === 'checkout' &&
			section === 'woocommerce_payments'
		);
	}

	render() {
		const { isEmbedded, ...restProps } = this.props;
		const { location, page } = this.props;
		const { breadcrumbs } = page;
		const query = this.getQuery( location && location.search );

		return (
			<SlotFillProvider>
				<div className="woocommerce-layout">
					<Header
						sections={
							isFunction( breadcrumbs )
								? breadcrumbs( this.props )
								: breadcrumbs
						}
						isEmbedded={ isEmbedded }
						query={ query }
					/>
					<TransientNotices />
					{ ! isEmbedded && (
						<PrimaryLayout>
							<div className="woocommerce-layout__main">
								<Controller { ...restProps } query={ query } />
							</div>
						</PrimaryLayout>
					) }

					{ isEmbedded && this.isWCPaySettingsPage() && (
						<Suspense fallback={ null }>
							<WCPayUsageModal />
						</Suspense>
					) }
				</div>
			</SlotFillProvider>
		);
	}
}

_Layout.propTypes = {
	isEmbedded: PropTypes.bool,
	page: PropTypes.shape( {
		container: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.object, // Support React.lazy
		] ),
		path: PropTypes.string,
		breadcrumbs: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.arrayOf(
				PropTypes.oneOfType( [
					PropTypes.arrayOf( PropTypes.string ),
					PropTypes.string,
				] )
			),
		] ).isRequired,
		wpOpenMenu: PropTypes.string,
	} ).isRequired,
};

const Layout = compose(
	withPluginsHydration( {
		...( window.wcSettings.plugins || {} ),
		jetpackStatus:
			( window.wcSettings.dataEndpoints &&
				window.wcSettings.dataEndpoints.jetpackStatus ) ||
			false,
	} ),
	withSelect( ( select, { isEmbedded } ) => {
		// Embedded pages don't send plugin info to Tracks.
		if ( isEmbedded ) {
			return;
		}

		const {
			getActivePlugins,
			getInstalledPlugins,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			isJetpackConnected: isJetpackConnected(),
			installedPlugins: getInstalledPlugins(),
		};
	} )
)( _Layout );

const _PageLayout = () => {
	const { currentUserCan } = useUser();

	return (
		<Router history={ getHistory() }>
			<Switch>
				{ getPages()
					.filter(
						( page ) =>
							! page.capability ||
							currentUserCan( page.capability )
					)
					.map( ( page ) => {
						return (
							<Route
								key={ page.path }
								path={ page.path }
								exact
								render={ ( props ) => (
									<Layout page={ page } { ...props } />
								) }
							/>
						);
					} ) }
			</Switch>
		</Router>
	);
};

export const PageLayout = compose(
	window.wcSettings.preloadOptions
		? withOptionsHydration( {
				...window.wcSettings.preloadOptions,
		  } )
		: identity
)( _PageLayout );

const _EmbedLayout = () => (
	<Layout
		page={ {
			breadcrumbs: getSetting( 'embedBreadcrumbs', [] ),
		} }
		isEmbedded
	/>
);

export const EmbedLayout = compose(
	window.wcSettings.preloadOptions
		? withOptionsHydration( {
				...window.wcSettings.preloadOptions,
		  } )
		: identity
)( _EmbedLayout );
