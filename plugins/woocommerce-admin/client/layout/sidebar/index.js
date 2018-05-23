/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { IconButton, TabPanel } from '@wordpress/components';
import { uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import Activity from 'dashboard/activity';
import SidebarHeader from './header';
import Count from 'components/count';

class Sidebar extends Component {
	getTabs() {
		return [
			{
				name: 'insights',
				title: (
					<span>
						{ __( 'Insights', 'woo-dash' ) } <Count count={ 3 } />
					</span>
				),
				className: 'woo-dash__sidebar-tab',
			},
			{
				name: 'orders',
				title: (
					<span>
						{ __( 'Orders', 'woo-dash' ) } <Count count={ 1 } />
					</span>
				),
				className: 'woo-dash__sidebar-tab',
			},
			{
				name: 'reviews',
				title: (
					<span>
						{ __( 'Reviews', 'woo-dash' ) } <Count count={ 7 } />
					</span>
				),
				className: 'woo-dash__sidebar-tab',
			},
		];
	}

	render() {
		const { isOpen, onToggle } = this.props;
		const className = classnames( 'woo-dash__secondary', {
			'is-opened': isOpen,
		} );
		const headerId = uniqueId( 'sidebar-header_' );
		const tabs = this.getTabs();

		return (
			<aside className={ className } aria-labelledby={ headerId }>
				<header className="woo-dash__sidebar-top">
					<h2 className="woo-dash__sidebar-title" id={ headerId }>
						{ __( 'Store Activity', 'woo-dash' ) }
					</h2>
					<div className="woo-dash__sidebar-toggle">
						<IconButton
							className="woo-dash__sidebar-button"
							onClick={ onToggle }
							icon="no-alt"
							label={ __( 'Close Sidebar', 'woo-dash' ) }
						/>
					</div>
				</header>
				<TabPanel className="woo-dash__sidebar-tabs" activeClass="is-active" tabs={ tabs }>
					{ selectedTabName => {
						return (
							<Fragment>
								<h3>Section: { selectedTabName }</h3>
								<SidebarHeader label={ __( 'Today', 'woo-dash' ) } />
								<Activity />
							</Fragment>
						);
					} }
				</TabPanel>
			</aside>
		);
	}
}

export default Sidebar;
