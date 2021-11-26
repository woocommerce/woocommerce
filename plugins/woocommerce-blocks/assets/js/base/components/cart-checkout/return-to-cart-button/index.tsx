/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CART_URL } from '@woocommerce/block-settings';
import { Icon, arrowBack } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';

interface ReturnToCartButtonProps {
	link?: string;
}

const ReturnToCartButton = ( {
	link,
}: ReturnToCartButtonProps ): JSX.Element => {
	return (
		<a
			href={ link || CART_URL }
			className="wc-block-components-checkout-return-to-cart-button"
		>
			<Icon srcElement={ arrowBack } />
			{ __( 'Return to Cart', 'woo-gutenberg-products-block' ) }
		</a>
	);
};

export default ReturnToCartButton;
