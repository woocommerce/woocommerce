/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Count from 'components/count';
import { H, Section } from 'layout/section';
import OrdersList from './orders';

class ActivityPanel extends Component {
	constructor() {
		super( ...arguments );
		this.togglePanel = this.togglePanel.bind( this );
		this.state = {
			isOpen: false,
			currentTab: '',
		};
	}

	togglePanel( tabName ) {
		const { currentTab } = this.state;
		if ( tabName === this.state.currentTab || '' === currentTab ) {
			this.setState( state => ( {
				isOpen: ! state.isOpen,
				currentTab: state.isOpen ? '' : tabName,
			} ) );
			return;
		}

		this.setState( { currentTab: tabName } );
	}

	getTabs() {
		return [
			{
				name: 'orders',
				title: (
					<span>
						{ __( 'Orders', 'woo-dash' ) }{' '}
						<Count count={ 1 } label={ sprintf( __( '%d Unfulfilled', 'woo-dash' ), 3 ) } />
					</span>
				),
				className: 'woocommerce-layout__activity-panel-tab',
			},
			{
				name: 'reviews',
				title: (
					<span>
						{ __( 'Reviews', 'woo-dash' ) } <Count count={ 7 } />
					</span>
				),
				className: 'woocommerce-layout__activity-panel-tab',
			},
			{
				name: 'stock',
				title: <span>{ __( 'Stock', 'woo-dash' ) }</span>,
				className: 'woocommerce-layout__activity-panel-tab',
			},
		];
	}

	renderPanel( tab ) {
		switch ( tab ) {
			case 'orders':
				return <OrdersList />;
			default:
				return <p>Coming soon…</p>;
		}
	}

	render() {
		const { isOpen } = this.state;
		const tabs = this.getTabs();

		return (
			<TabPanel
				className="woocommerce-layout__activity-panel-tabs"
				activeClass="is-active"
				tabs={ tabs }
				onSelect={ this.togglePanel }
			>
				{ selectedTabName => {
					if ( ! isOpen ) {
						return null;
					}
					return (
						<Section component="div" className="woocommerce-layout__activity-panel-content">
							<H>Section: { selectedTabName }</H>
							{ this.renderPanel( selectedTabName ) }
						</Section>
					);
				} }
			</TabPanel>
		);
	}
}

export default ActivityPanel;
