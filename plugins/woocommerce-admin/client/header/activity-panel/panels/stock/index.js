/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { EmptyContent, Section } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../../activity-card';
import ActivityHeader from '../../activity-header';
import Gridicon from 'gridicons';
import ProductStockCard from './card';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';

class StockPanel extends Component {
	renderEmptyCard() {
		return (
			<ActivityCard
				className="woocommerce-empty-review-activity-card"
				title={ __( 'Your stock is in good shape.', 'woocommerce-admin' ) }
				icon={ <Gridicon icon="checkmark" size={ 48 } /> }
			>
				{ __( 'You currently have no products running low on stock.', 'woocommerce-admin' ) }
			</ActivityCard>
		);
	}

	renderProducts() {
		const { products } = this.props;

		if ( products.length === 0 ) {
			return this.renderEmptyCard();
		}

		return products.map( product => <ProductStockCard key={ product.id } product={ product } /> );
	}

	render() {
		const { isError, isRequesting, products } = this.props;

		if ( isError ) {
			const title = __(
				'There was an error getting your low stock products. Please try again.',
				'woocommerce-admin'
			);
			const actionLabel = __( 'Reload', 'woocommerce-admin' );
			const actionCallback = () => {
				// @todo Add tracking for how often an error is displayed, and the reload action is clicked.
				window.location.reload();
			};

			return (
				<Fragment>
					<EmptyContent
						title={ title }
						actionLabel={ actionLabel }
						actionURL={ null }
						actionCallback={ actionCallback }
					/>
				</Fragment>
			);
		}

		const title =
			isRequesting || products.length > 0
				? __( 'Stock', 'woocommerce-admin' )
				: __( 'No products with low stock', 'woocommerce-admin' );
		return (
			<Fragment>
				<ActivityHeader title={ title } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-stock-activity-card"
							hasAction
							lines={ 1 }
						/>
					) : (
						this.renderProducts()
					) }
				</Section>
			</Fragment>
		);
	}
}

StockPanel.propTypes = {
	products: PropTypes.array.isRequired,
	isError: PropTypes.bool,
	isRequesting: PropTypes.bool,
};

StockPanel.defaultProps = {
	products: [],
	isError: false,
	isRequesting: false,
};

export default compose(
	withSelect( select => {
		const { getItems, getItemsError, isGetItemsRequesting } = select( 'wc-api' );

		const productsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			low_in_stock: true,
			status: 'publish',
		};

		const products = Array.from( getItems( 'products', productsQuery ).values() );
		const isError = Boolean( getItemsError( 'products', productsQuery ) );
		const isRequesting = isGetItemsRequesting( 'products', productsQuery );

		return { products, isError, isRequesting };
	} )
)( StockPanel );
