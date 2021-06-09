/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { EmptyContent, Section } from '@woocommerce/components';
import { ITEMS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ActivityCardPlaceholder } from '../../../header/activity-panel/activity-card';
import { ProductStockCard } from './card';
import { getLowStockCountQuery } from '../orders/utils';

const productsQuery = {
	page: 1,
	per_page: 5,
	low_in_stock: true,
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
		const {
			invalidateResolution,
			updateProductStock,
			products,
		} = this.props;

		const success = await updateProductStock( product, quantity );

		if ( success ) {
			// Request more low stock products.
			invalidateResolution( 'getItems', [ 'products', productsQuery ] );
			if ( products.length < 2 ) {
				invalidateResolution( 'getItemsTotalCount', [
					'products',
					getLowStockCountQuery,
					null,
				] );
			}
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
		const {
			countLowStockProducts,
			isError,
			isRequesting,
			products,
		} = this.props;

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
				<EmptyContent
					title={ title }
					actionLabel={ actionLabel }
					actionURL={ null }
					actionCallback={ actionCallback }
				/>
			);
		}

		// Show placeholders only for the first products fetch.
		if ( isRequesting || ! products.length ) {
			const numPlaceholders = Math.min( 5, countLowStockProducts ?? 1 );
			const placeholders = Array.from(
				new Array( numPlaceholders )
			).map( ( v, idx ) => (
				<ActivityCardPlaceholder
					key={ idx }
					className="woocommerce-stock-activity-card"
					hasAction
					lines={ 1 }
				/>
			) );

			return <Section>{ placeholders }</Section>;
		}

		return <Section>{ this.renderProducts() }</Section>;
	}
}

StockPanel.propTypes = {
	countLowStockProducts: PropTypes.number,
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
		const { getItems, getItemsError, isResolving } = select(
			ITEMS_STORE_NAME
		);

		const products = Array.from(
			getItems( 'products', productsQuery ).values()
		);
		const isError = Boolean( getItemsError( 'products', productsQuery ) );
		const isRequesting = isResolving( 'getItems', [
			'products',
			productsQuery,
		] );

		return { products, isError, isRequesting };
	} ),
	withDispatch( ( dispatch ) => {
		const { invalidateResolution, updateProductStock } = dispatch(
			ITEMS_STORE_NAME
		);
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			invalidateResolution,
			updateProductStock,
		};
	} )
)( StockPanel );
