/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import Title from '@woocommerce/base-components/title';

const CartLineItemsTitle = ( { itemCount = 1 } ) => {
	return (
		<Title headingLevel="2">
			{ sprintf(
				// Translators: %d is the count of items in the cart.
				_n(
					'Your cart (%d item)',
					'Your cart (%d items)',
					itemCount,
					'woocommerce'
				),
				itemCount
			) }
		</Title>
	);
};

CartLineItemsTitle.propTypes = {
	itemCount: PropTypes.number,
};

export default CartLineItemsTitle;
