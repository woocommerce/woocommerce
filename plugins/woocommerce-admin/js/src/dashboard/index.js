/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Button, withAPIData } from '@wordpress/components';
import { Component, compose } from '@wordpress/element';

/**
 * External dependencies
 */
import './style.scss';
import useFilters from '../use-filters';

class Dashboard extends Component {
	render() {
		const { products } = this.props;
		const totalProducts = products.data && products.data.length || 0;
		return (
			<div>
				<h2>{ applyFilters( 'woodash.example2', __( 'Example Widget', 'woo-dash' ) ) }</h2>
				<div className="wd_widget">
					<div className="wd_widget-item">
						{ sprintf( _n( '%d New Customer', '%d New Customers', 4, 'woo-dash' ), 4 ) }
					</div>
					<div className="wd_widget-item">
						{ sprintf( _n( '%d New Order', '%d New Orders', 10, 'woo-dash' ), 10 ) }
					</div>
					<div className="wd_widget-item">
						{ sprintf( _n( '%d Product', '%d Products', totalProducts, 'woo-dash' ), totalProducts ) }
					</div>
					<div className="wd_widget-item">
						<Button isPrimary href="#">{ __( 'View Orders', 'woo-dash' ) }</Button>
					</div>
				</div>

				<h3>{ applyFilters( 'woodash.example', __( 'Example Text', 'woo-dash' ) ) }</h3>
			</div>
		);
	}
}

export default compose( [
	useFilters( [ 'woodash.example', 'woodash.example2' ] ),
	withAPIData( () => ( {
		products: '/wc/v2/products',
	} ) ),
] )( Dashboard );
