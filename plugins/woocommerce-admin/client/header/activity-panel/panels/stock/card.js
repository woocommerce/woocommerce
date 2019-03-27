/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Link, ProductImage } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../../activity-card';

class ProductStockCard extends Component {
	render() {
		const { product } = this.props;
		const title = (
			<Link href={ 'post.php?action=edit&post=' + product.id } type="wp-admin">
				{ product.name }
			</Link>
		);

		return (
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
		);
	}
}

export default ProductStockCard;
