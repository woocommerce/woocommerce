/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import Title from '@woocommerce/base-components/title';

const CartLineItemsTitle = ( {
	itemCount = 1,
}: {
	itemCount: number;
} ): JSX.Element => {
	return (
		<Title headingLevel="2">
			{ sprintf(
				/* translators: %d is the count of items in the cart. */
				_n(
					'Your cart (%d item)',
					'Your cart (%d items)',
					itemCount,
					'woo-gutenberg-products-block'
				),
				itemCount
			) }
		</Title>
	);
};

export default CartLineItemsTitle;
