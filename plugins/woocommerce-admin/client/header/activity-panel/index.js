/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import clickOutside from 'react-click-outside';
import { Component } from '@wordpress/element';
import Gridicon from 'gridicons';
import { IconButton, NavigableMenu } from '@wordpress/components';
import { partial, uniqueId, find } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanelToggleBubble from './toggle-bubble';
import { H, Section } from '@woocommerce/components';
import OrdersPanel from './panels/orders';
import StockPanel from './panels/stock';
import ReviewsPanel from './panels/reviews';
import WordPressNotices from './wordpress-notices';

class ActivityPanel extends Component {
	constructor() {
		super( ...arguments );
		this.togglePanel = this.togglePanel.bind( this );
		this.toggleMobile = this.toggleMobile.bind( this );
		this.renderTab = this.renderTab.bind( this );
		this.updateNoticeFlag = this.updateNoticeFlag.bind( this );
		this.state = {
			isPanelOpen: false,
			mobileOpen: false,
			currentTab: '',
			hasWordPressNotices: false,
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
		const tabs = this.getTabs();
		this.setState( state => ( {
			mobileOpen: ! state.mobileOpen,
			currentTab: state.mobileOpen ? '' : tabs[ 0 ].name,
			isPanelOpen: ! state.mobileOpen,
		} ) );
	}

	handleClickOutside() {
		const { isPanelOpen, currentTab } = this.state;

		if ( isPanelOpen ) {
			this.togglePanel( currentTab );
		}
	}

	updateNoticeFlag( noticeCount ) {
		this.setState( {
			hasWordPressNotices: noticeCount > 0,
		} );
	}

	// TODO Pull in dynamic unread status/count
	getTabs() {
		return [
			{
				name: 'orders',
				title: __( 'Orders', 'wc-admin' ),
				icon: <Gridicon icon="pages" />,
				unread: true,
			},
			{
				name: 'stock',
				title: __( 'Stock', 'wc-admin' ),
				icon: <Gridicon icon="clipboard" />,
				unread: false,
			},
			{
				name: 'reviews',
				title: __( 'Reviews', 'wc-admin' ),
				icon: <Gridicon icon="star" />,
				unread: false,
			},
		];
	}

	getPanelContent( tab ) {
		switch ( tab ) {
			case 'orders':
				return <OrdersPanel />;
			case 'stock':
				return <StockPanel />;
			case 'reviews':
				return <ReviewsPanel />;
			default:
				return null;
		}
	}

	renderPanel() {
		const { isPanelOpen, currentTab } = this.state;

		const tab = find( this.getTabs(), { name: currentTab } );
		if ( ! tab ) {
			return <div className="woocommerce-layout__activity-panel-wrapper" />;
		}

		const classNames = classnames( 'woocommerce-layout__activity-panel-wrapper', {
			'is-open': isPanelOpen,
		} );

		return (
			<div className={ classNames } tabIndex={ 0 } role="tabpanel" aria-label={ tab.title }>
				{ ( isPanelOpen && (
					<div
						className="woocommerce-layout__activity-panel-content"
						key={ 'activity-panel-' + currentTab }
						id={ 'activity-panel-' + currentTab }
					>
						{ this.getPanelContent( currentTab ) }
					</div>
				) ) ||
					null }
			</div>
		);
	}

	renderTab( tab, i ) {
		const { currentTab, isPanelOpen } = this.state;
		const className = classnames( 'woocommerce-layout__activity-panel-tab', {
			'is-active': tab.name === currentTab,
			'has-unread': tab.unread,
		} );

		const selected = tab.name === currentTab;
		let tabIndex = -1;

		// Only make this item tabbable if it is the currently selected item, or the panel is closed and the item is the first item
		// If wpnotices is currently selected, tabindex below should be  -1 and <WordPressNotices /> will become the tabbed element.
		if ( selected || ( ! isPanelOpen && i === 0 && 'wpnotices' !== currentTab ) ) {
			tabIndex = null;
		}

		return (
			<IconButton
				role="tab"
				className={ className }
				tabIndex={ tabIndex }
				aria-selected={ selected }
				aria-controls={ 'activity-panel-' + tab.name }
				key={ 'activity-panel-tab-' + tab.name }
				id={ 'activity-panel-tab-' + tab.name }
				onClick={ partial( this.togglePanel, tab.name ) }
				icon={ tab.icon }
			>
				{ tab.title }{' '}
				{ tab.unread && (
					<span className="screen-reader-text">{ __( 'unread activity', 'wc-admin' ) }</span>
				) }
			</IconButton>
		);
	}

	render() {
		const tabs = this.getTabs();
		const { currentTab, mobileOpen, hasWordPressNotices } = this.state;
		const headerId = uniqueId( 'activity-panel-header_' );
		const panelClasses = classnames( 'woocommerce-layout__activity-panel', {
			'is-mobile-open': this.state.mobileOpen,
		} );

		const hasUnread = hasWordPressNotices || tabs.some( tab => tab.unread );
		const viewLabel = hasUnread
			? __( 'View Activity Panel, you have unread activity', 'wc-admin' )
			: __( 'View Activity Panel', 'wc-admin' );

		return (
			<div>
				<H id={ headerId } className="screen-reader-text">
					{ __( 'Store Activity', 'wc-admin' ) }
				</H>
				<Section component="aside" id="woocommerce-activity-panel" aria-labelledby={ headerId }>
					<IconButton
						onClick={ this.toggleMobile }
						icon={
							mobileOpen ? (
								<Gridicon icon="cross-small" />
							) : (
								<ActivityPanelToggleBubble hasUnread={ hasUnread } />
							)
						}
						label={ mobileOpen ? __( 'Close Activity Panel', 'wc-admin' ) : viewLabel }
						aria-expanded={ mobileOpen }
						tooltip={ false }
						className="woocommerce-layout__activity-panel-mobile-toggle"
					/>
					<div className={ panelClasses }>
						<NavigableMenu
							role="tablist"
							orientation="horizontal"
							className="woocommerce-layout__activity-panel-tabs"
						>
							{ tabs && tabs.map( this.renderTab ) }
							<WordPressNotices
								showNotices={ 'wpnotices' === currentTab }
								togglePanel={ this.togglePanel }
								onCountUpdate={ this.updateNoticeFlag }
							/>
						</NavigableMenu>
						{ this.renderPanel() }
					</div>
				</Section>
			</div>
		);
	}
}

export default clickOutside( ActivityPanel );
