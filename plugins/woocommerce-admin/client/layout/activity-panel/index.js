/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import Gridicon from 'gridicons';
import { IconButton } from '@wordpress/components';
import { partial } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import { Section } from 'layout/section';
import OrdersList from './orders';
import WordPressNotices from './wordpress-notices';

class ActivityPanel extends Component {
	constructor() {
		super( ...arguments );
		this.togglePanel = this.togglePanel.bind( this );
		this.toggleMobile = this.toggleMobile.bind( this );
		this.renderTab = this.renderTab.bind( this );
		this.state = {
			isPanelOpen: false,
			mobileOpen: false,
			currentTab: '',
		};
	}

	togglePanel( tabName ) {
		// The WordPress Notices tab is handled differently, since they are displayed inline, so the panel should be closed,
		// Close behavior of the expanded notices is based on current tab.
		if ( 'wpnotices' === tabName ) {
			this.setState( state => ( {
				currentTab: 'wpnotices' === state.currentTab ? '' : tabName,
				mobileOpen: 'wpnotices' !== state.currentTab,
				isPanelOpen: false,
			} ) );
			return;
		}

		this.setState( state => {
			if (
				tabName === state.currentTab ||
				'' === state.currentTab ||
				'wpnotices' === state.currentTab
			) {
				return {
					isPanelOpen: ! state.isPanelOpen,
					currentTab: state.isPanelOpen ? '' : tabName,
					mobileOpen: ! state.isPanelOpen,
				};
			}
			return { currentTab: tabName };
		} );
	}

	// On smaller screen, the panel buttons are hidden behind a toggle.
	toggleMobile() {
		this.setState( state => ( {
			mobileOpen: ! state.mobileOpen,
			currentTab: state.mobileOpen ? '' : 'inbox',
			isPanelOpen: ! state.mobileOpen,
		} ) );
	}

	// TODO Replace with correct icons
	getTabs() {
		return [
			{
				name: 'inbox',
				title: __( 'Inbox', 'woo-dash' ),
				icon: <Gridicon icon="mail" />,
			},
			{
				name: 'orders',
				title: __( 'Orders', 'woo-dash' ),
				icon: <Gridicon icon="pages" />,
			},
			{
				name: 'stock',
				title: __( 'Stock', 'woo-dash' ),
				icon: <Gridicon icon="clipboard" />,
			},
			{
				name: 'reviews',
				title: __( 'Reviews', 'woo-dash' ),
				icon: <Gridicon icon="star" />,
			},
		];
	}

	renderPanel() {
		const { isPanelOpen, currentTab } = this.state;

		if ( ! isPanelOpen ) {
			return null;
		}

		let panelContent;

		switch ( currentTab ) {
			case 'orders':
				panelContent = <OrdersList />;
				break;
			default:
				panelContent = <p>Coming soon…</p>;
		}

		return (
			<Section component="div" className="woocommerce-layout__activity-panel-content">
				{ panelContent }
			</Section>
		);
	}

	renderTab( tab ) {
		const { currentTab } = this.state;
		const className = classnames( 'woocommerce-layout__activity-panel-tab', {
			'is-active': tab.name === currentTab,
		} );

		return (
			<IconButton
				key={ tab.name }
				className={ className }
				onClick={ partial( this.togglePanel, tab.name ) }
				icon={ tab.icon }
			>
				{ tab.title }
			</IconButton>
		);
	}

	render() {
		const tabs = this.getTabs();
		const { currentTab, mobileOpen } = this.state;

		const panelClasses = classnames( 'woocommerce-layout__activity-panel', {
			'is-mobile-open': this.state.mobileOpen,
		} );

		// TODO Replace the mobile toggle with the Woo bubble Gridicon once it has been added.
		return (
			<Fragment>
				<IconButton
					onClick={ this.toggleMobile }
					icon={ mobileOpen ? <Gridicon icon="cross-small" /> : 'admin-generic' }
					label={ __( 'View Activity Panel' ) }
					aria-expanded={ mobileOpen }
					tooltip={ false }
					className="woocommerce-layout__activity-panel-mobile-toggle"
				/>
				<div className={ panelClasses }>
					<div className="woocommerce-layout__activity-panel-tabs">
						{ tabs && tabs.map( this.renderTab ) }
						<WordPressNotices
							showNotices={ 'wpnotices' === currentTab }
							togglePanel={ this.togglePanel }
						/>
					</div>
					{ this.renderPanel() }
				</div>
			</Fragment>
		);
	}
}

export default ActivityPanel;
