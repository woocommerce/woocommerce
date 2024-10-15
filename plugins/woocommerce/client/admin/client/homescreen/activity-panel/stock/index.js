/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { Section } from '@woocommerce/components';
import { ITEMS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ActivityCardPlaceholder } from '~/activity-panel/activity-card';
import { ProductStockCard } from './card';
import { getLowStockCountQuery } from '../orders/utils';

const productsQuery = {
	page: 1,
	per_page: 5,
	status: 'publish',
	_fields: [
		'attributes',
		'id',
		'images',
		'last_order_date',
		'low_stock_amount',
		'name',
		'parent_id',
		'stock_quantity',
		'type',
	],
};
export class StockPanel extends Component {
	constructor( props ) {
		super( props );

		this.updateStock = this.updateStock.bind( this );
	}

	async updateStock( product, quantity ) {
		const { invalidateResolution, updateProductStock } = this.props;
		const success = await updateProductStock( product, quantity );

		if ( success ) {
			// Request more low stock products.
			invalidateResolution( 'getItems', [
				'products/low-in-stock',
				productsQuery,
			] );
			invalidateResolution( 'getItemsTotalCount', [
				'products/count-low-in-stock',
				getLowStockCountQuery,
				null,
			] );
		}

		return success;
	}

	renderProducts() {
		const { products, createNotice } = this.props;

		return products.map( ( product ) => (
			<ProductStockCard
				key={ product.id }
				product={ product }
				updateProductStock={ this.updateStock }
				createNotice={ createNotice }
			/>
		) );
	}

	render() {
		const { lowStockProductsCount, isError, isRequesting, products } =
			this.props;

		if ( isError ) {
			throw new Error(
				'Failed to load low stock products, Raise error to trigger ErrorBoundary'
			);
		}

		// Show placeholders only for the first products fetch.
		if ( isRequesting || ! products.length ) {
			const numPlaceholders = Math.min( 5, lowStockProductsCount ?? 1 );
			const placeholders = Array.from( new Array( numPlaceholders ) ).map(
				( v, idx ) => (
					<ActivityCardPlaceholder
						key={ idx }
						className="woocommerce-stock-activity-card"
						hasAction
						lines={ 1 }
					/>
				)
			);

			return <Section>{ placeholders }</Section>;
		}

		return <Section>{ this.renderProducts() }</Section>;
	}
}

StockPanel.propTypes = {
	lowStockProductsCount: PropTypes.number,
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
	withSelect( ( select ) => {
		const { getItems, getItemsError, isResolving } =
			select( ITEMS_STORE_NAME );

		const products = Array.from(
			getItems( 'products/low-in-stock', productsQuery ).values()
		);
		const isError = Boolean(
			getItemsError( 'products/low-in-stock', productsQuery )
		);
		const isRequesting = isResolving( 'getItems', [
			'products/low-in-stock',
			productsQuery,
		] );

		return { products, isError, isRequesting };
	} ),
	withDispatch( ( dispatch ) => {
		const { invalidateResolution, updateProductStock } =
			dispatch( ITEMS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			invalidateResolution,
			updateProductStock,
		};
	} )
)( StockPanel );
