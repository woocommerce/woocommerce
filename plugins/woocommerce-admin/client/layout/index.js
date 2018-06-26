/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { Router, Route, Switch } from 'react-router-dom';
import { Slot } from 'react-slot-fill';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import Header from 'components/header';
import { Controller, getPages } from './controller';
import history from 'lib/history';
import Notices from './notices';
import Sidebar from './sidebar';

class Layout extends Component {
	constructor() {
		super( ...arguments );
		this.toggleSidebar = this.toggleSidebar.bind( this );
		this.state = {
			isSidebarOpen: false,
		};
	}

	toggleSidebar() {
		this.setState( state => ( { isSidebarOpen: ! state.isSidebarOpen } ) );
	}

	render() {
		// TODO The activity panel will now always be collapsed. We should refactor some of these classnames.
		const className = classnames( 'woocommerce-layout', {
			'has-visible-sidebar': false,
			'has-hidden-sidebar': true,
		} );
		const headerProps = {
			onToggle: this.toggleSidebar,
			isSidebarOpen: this.state.isSidebarOpen,
		};

		const { isEmbeded, ...restProps } = this.props;

		return (
			<div className={ className }>
				<Slot name="header" fillChildProps={ headerProps } />
				<div className="woocommerce-layout__primary" id="woocommerce-layout__primary">
					<Notices />
					{ ! isEmbeded && (
						<div className="woocommerce-layout__main">
							<Controller { ...restProps } />
						</div>
					) }
				</div>

				<Sidebar isOpen={ this.state.isSidebarOpen } onToggle={ this.toggleSidebar } />
			</div>
		);
	}
}

Layout.propTypes = {
	isEmbededLayout: PropTypes.bool,
};

export class PageLayout extends Component {
	render() {
		return (
			<Router history={ history }>
				<Switch>
					{ getPages().map( page => {
						return <Route key={ page.path } path={ page.path } exact component={ Layout } />;
					} ) }
					<Route component={ Layout } />
				</Switch>
			</Router>
		);
	}
}

export class EmbedLayout extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ wcSettings.embedBreadcrumbs } isEmbedded />
				<Layout isEmbeded />
			</Fragment>
		);
	}
}
