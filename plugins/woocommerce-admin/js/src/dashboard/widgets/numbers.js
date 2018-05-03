/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, withAPIData } from '@wordpress/components';
import { Component, compose } from '@wordpress/element';

class WidgetNumbers extends Component {
	render() {
		const { orders, products } = this.props;
		const totalOrders = orders.data && orders.data.length || 0;
		const totalProducts = products.data && products.data.length || 0;
		return (
			<div className="wd_widget">
				<div className="wd_widget-item">
					{ sprintf( _n( '%d New Customer', '%d New Customers', 4, 'woo-dash' ), 4 ) }
				</div>
				<div className="wd_widget-item">
					{ sprintf( _n( '%d New Order', '%d New Orders', totalOrders, 'woo-dash' ), totalOrders ) }
				</div>
				<div className="wd_widget-item">
					{ sprintf( _n( '%d Product', '%d Products', totalProducts, 'woo-dash' ), totalProducts ) }
				</div>
				<div className="wd_widget-item">
					<Button isPrimary href="#">{ __( 'View Orders', 'woo-dash' ) }</Button>
				</div>
			</div>
		);
	}
}

export default compose( [
	withAPIData( () => ( {
		orders: '/wc/v2/orders?status=processing',
		products: '/wc/v2/products',
	} ) ),
] )( WidgetNumbers );
