/**
 * External dependencies
 */
import { CART_URL } from '@woocommerce/block-settings';
import { Icon, arrowLeft } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

interface ReturnToCartButtonProps {
	href?: string | undefined;
	children: React.ReactNode;
}

const ReturnToCartButton = ( {
	href,
	children,
}: ReturnToCartButtonProps ): JSX.Element | null => {
	const cartLink = href || CART_URL;
	if ( ! cartLink ) {
		return null;
	}
	return (
		<a
			href={ cartLink }
			className="wc-block-components-checkout-return-to-cart-button"
		>
			<Icon icon={ arrowLeft } />
			{ children }
		</a>
	);
};

export default ReturnToCartButton;
