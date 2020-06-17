/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import {
	useInnerBlockLayoutContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Product Stock Indicator Block Component.
 *
 * @param {Object} props             Incoming props.
 * @param {string} [props.className] CSS Class name for the component.
 * @param {Object} [props.product]   Optional product object. Product from context will be used if
 *                                   this is not provided.
 * @return {*} The component.
 */
const Block = ( { className, ...props } ) => {
	const { parentClassName } = useInnerBlockLayoutContext();
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product || {};

	if ( isEmpty( product ) || ! product.is_purchasable ) {
		return null;
	}

	const inStock = !! product.is_in_stock;
	const lowStock = product.low_stock_remaining;
	const isBackordered = product.is_on_backorder;

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-stock-indicator',
				`${ parentClassName }__stock-indicator`,
				{
					'wc-block-components-product-stock-indicator--in-stock': inStock,
					'wc-block-components-product-stock-indicator--out-of-stock': ! inStock,
					'wc-block-components-product-stock-indicator--low-stock': !! lowStock,
					'wc-block-components-product-stock-indicator--available-on-backorder': !! isBackordered,
				}
			) }
		>
			{ lowStock
				? lowStockText( lowStock )
				: stockText( inStock, isBackordered ) }
		</div>
	);
};

const lowStockText = ( lowStock ) => {
	return sprintf(
		/* translators: %d stock amount (number of items in stock for product) */
		__( '%d left in stock', 'woo-gutenberg-products-block' ),
		lowStock
	);
};

const stockText = ( inStock, isBackordered ) => {
	if ( isBackordered ) {
		return __( 'Available on backorder', 'woo-gutenberg-products-block' );
	}

	return inStock
		? __( 'In Stock', 'woo-gutenberg-products-block' )
		: __( 'Out of Stock', 'woo-gutenberg-products-block' );
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
