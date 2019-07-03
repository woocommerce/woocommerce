/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { useFilters } from '@woocommerce/components';
import { Router, Route, Switch } from 'react-router-dom';
import { Slot } from 'react-slot-fill';
import PropTypes from 'prop-types';
import { get } from 'lodash';

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
		return (
			<div className="woocommerce-layout">
				<Slot name="header" />
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
// Use the useFilters HoC so PageLayout is re-rendered when the filter is used to add new pages
export const PageLayout = useFilters( PAGES_FILTER )( _PageLayout );

export class EmbedLayout extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ wcSettings.embedBreadcrumbs } isEmbedded />
				<Layout isEmbedded />
			</Fragment>
		);
	}
}
