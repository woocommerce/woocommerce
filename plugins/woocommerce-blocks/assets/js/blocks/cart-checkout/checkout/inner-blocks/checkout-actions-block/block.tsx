/**
 * External dependencies
 */
import classnames from 'classnames';
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
	className,
}: {
	cartPageId: number;
	showReturnToCart: boolean;
	className?: string;
} ): JSX.Element => {
	return (
		<div
			className={ classnames( 'wc-block-checkout__actions', className ) }
		>
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
