/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

const Block = ( {
	cartPageId,
	showReturnToCart,
}: {
	cartPageId: number;
	showReturnToCart: boolean;
} ): JSX.Element => {
	return (
		<div className="wc-block-checkout__actions">
			{ showReturnToCart && (
				<ReturnToCartButton
					link={ getSetting( 'page-' + cartPageId, false ) }
				/>
			) }
			<PlaceOrderButton />
		</div>
	);
};

export default Block;
