/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, withAPIData } from '@wordpress/components';
import { Component, compose } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import { EllipsisMenu, MenuItem, MenuTitle } from 'components/ellipsis-menu';
import { SummaryList, SummaryNumber } from 'components/summary';
import './style.scss';

class StorePerformance extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			showCustomers: true,
			showProducts: true,
			showOrders: true,
		};

		this.toggle = this.toggle.bind( this );
	}

	toggle( type ) {
		return () => {
			this.setState( state => ( { [ type ]: ! state[ type ] } ) );
		};
	}

	renderMenu() {
		return (
			<EllipsisMenu label={ __( 'Choose which analytics to display', 'wc-admin' ) }>
				<MenuTitle>{ __( 'Display Stats:', 'wc-admin' ) }</MenuTitle>
				<MenuItem onInvoke={ this.toggle( 'showCustomers' ) }>
					<ToggleControl
						label={ __( 'Show Customers', 'wc-admin' ) }
						checked={ this.state.showCustomers }
						onChange={ this.toggle( 'showCustomers' ) }
					/>
				</MenuItem>
				<MenuItem onInvoke={ this.toggle( 'showProducts' ) }>
					<ToggleControl
						label={ __( 'Show Products', 'wc-admin' ) }
						checked={ this.state.showProducts }
						onChange={ this.toggle( 'showProducts' ) }
					/>
				</MenuItem>
				<MenuItem onInvoke={ this.toggle( 'showOrders' ) }>
					<ToggleControl
						label={ __( 'Show Orders', 'wc-admin' ) }
						checked={ this.state.showOrders }
						onChange={ this.toggle( 'showOrders' ) }
					/>
				</MenuItem>
			</EllipsisMenu>
		);
	}

	render() {
		const { orders, products } = this.props;
		const totalOrders = ( orders.data && orders.data.length ) || 0;
		const totalProducts = ( products.data && products.data.length ) || 0;
		const { showCustomers, showProducts, showOrders } = this.state;

		return (
			<Card
				title={ __( 'Store Performance', 'wc-admin' ) }
				menu={ this.renderMenu() }
				className="woocommerce-dashboard__store-performance"
			>
				<SummaryList>
					{ showCustomers && (
						<SummaryNumber value={ '2' } label={ __( 'New Customers', 'wc-admin' ) } delta={ 15 } />
					) }
					{ showProducts && (
						<SummaryNumber value={ totalProducts } label={ __( 'Total Products', 'wc-admin' ) } />
					) }
					{ showOrders && (
						<SummaryNumber
							value={ totalOrders }
							selected
							label={ __( 'Total Orders', 'wc-admin' ) }
							delta={ -6 }
						/>
					) }
				</SummaryList>
			</Card>
		);
	}
}

export default compose( [
	withAPIData( () => ( {
		orders: '/wc/v2/orders?status=processing',
		products: '/wc/v2/products',
	} ) ),
] )( StorePerformance );
