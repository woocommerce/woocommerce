/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { useFilters } from '@woocommerce/components';
import { Router, Route, Switch } from 'react-router-dom';
import PropTypes from 'prop-types';
import { get, isFunction } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import { Controller, getPages, PAGES_FILTER } from './controller';
import Header from 'header';
import Notices from './notices';
import { recordPageView } from 'lib/tracks';
import TransientNotices from './transient-notices';
import StoreAlerts from './store-alerts';
import { REPORTS_FILTER } from 'analytics/report';

export class PrimaryLayout extends Component {
	render() {
		const { children } = this.props;
		return (
			<div className="woocommerce-layout__primary" id="woocommerce-layout__primary">
				{ window.wcAdminFeatures[ 'store-alerts' ] && <StoreAlerts /> }
				<Notices />
				{ children }
			</div>
		);
	}
}

class Layout extends Component {
	componentDidMount() {
		this.recordPageViewTrack();
		document.body.classList.remove( 'woocommerce-admin-is-loading' );
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
		const pathname = get( this.props, 'location.pathname' );
		if ( ! pathname ) {
			return;
		}

		// Remove leading slash, and camel case remaining pathname
		let path = pathname.substring( 1 ).replace( /\//g, '_' );

		// When pathname is `/` we are on the dashboard
		if ( path.length === 0 ) {
			path = 'dashboard';
		}

		recordPageView( path );
	}

	render() {
		const { isEmbedded, ...restProps } = this.props;
		const { breadcrumbs } = this.props.page;
		return (
			<div className="woocommerce-layout">
				<Header
					sections={ isFunction( breadcrumbs ) ? breadcrumbs( this.props ) : breadcrumbs }
					isEmbedded={ isEmbedded }
				/>
				<TransientNotices />
				{ ! isEmbedded && (
					<PrimaryLayout>
						<div className="woocommerce-layout__main">
							<Controller { ...restProps } />
						</div>
					</PrimaryLayout>
				) }
			</div>
		);
	}
}

Layout.propTypes = {
	isEmbedded: PropTypes.bool,
	page: PropTypes.shape( {
		container: PropTypes.func.isRequired,
		path: PropTypes.string.isRequired,
		breadcrumbs: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.arrayOf(
				PropTypes.oneOfType( [ PropTypes.arrayOf( PropTypes.string ), PropTypes.string ] )
			),
		] ).isRequired,
		wpOpenMenu: PropTypes.string.isRequired,
	} ).isRequired,
};

class _PageLayout extends Component {
	render() {
		return (
			<Router history={ getHistory() }>
				<Switch>
					{ getPages().map( page => {
						return (
							<Route
								key={ page.path }
								path={ page.path }
								exact
								render={ props => <Layout page={ page } { ...props } /> }
							/>
						);
					} ) }
				</Switch>
			</Router>
		);
	}
}
// Use the useFilters HoC so PageLayout is re-rendered when filters are used to add new pages or reports
export const PageLayout = useFilters( [ PAGES_FILTER, REPORTS_FILTER ] )( _PageLayout );

export class EmbedLayout extends Component {
	render() {
		return (
			<Layout
				page={ {
					breadcrumbs: wcSettings.embedBreadcrumbs,
				} }
				isEmbedded
			/>
		);
	}
}
