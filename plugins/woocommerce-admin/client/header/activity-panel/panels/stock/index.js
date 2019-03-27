/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Section } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCardPlaceholder } from '../../activity-card';
import ActivityHeader from '../../activity-header';
import ProductStockCard from './card';

class StockPanel extends Component {
	render() {
		const products = [
			{
				id: 913,
				name: 'Octopus Tshirt',
				permalink: '',
				image: {
					src: '/wp-content/uploads/2018/12/img-206939428-150x150.png',
				},
				stock_quantity: 2,
			},
		];
		const isRequesting = false;

		return (
			<Fragment>
				<ActivityHeader title={ __( 'Stock', 'woocommerce-admin' ) } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-stock-activity-card"
							hasAction
							lines={ 1 }
						/>
					) : (
						products.map( product => <ProductStockCard key={ product.id } product={ product } /> )
					) }
				</Section>
			</Fragment>
		);
	}
}

export default StockPanel;
