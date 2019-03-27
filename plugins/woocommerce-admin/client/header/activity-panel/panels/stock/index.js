/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Link, ProductImage, Section } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../../activity-card';
import ActivityHeader from '../../activity-header';

class StockPanel extends Component {
	render() {
		const product = {
			id: 913,
			name: 'Octopus Tshirt',
			permalink: '',
			image: {
				src: '/wp-content/uploads/2018/12/img-206939428-150x150.png',
			},
			stock_quantity: 2,
		};
		const title = (
			<Link href={ 'post.php?action=edit&post=' + product.id } type="wp-admin">
				{ product.name }
			</Link>
		);

		return (
			<Fragment>
				<ActivityHeader title={ __( 'Stock', 'woocommerce-admin' ) } />
				<Section>
					<ActivityCard
						className="woocommerce-stock-activity-card"
						title={ title }
						icon={ <ProductImage product={ product } /> }
						actions={ <Button isDefault>{ __( 'Update stock', 'woocommerce-admin' ) }</Button> }
					>
						<span className="woocommerce-stock-activity-card__stock-quantity">
							{ sprintf( __( '%d in stock', 'woocommerce-admin' ), product.stock_quantity ) }
						</span>
					</ActivityCard>
				</Section>
			</Fragment>
		);
	}
}

export default StockPanel;
