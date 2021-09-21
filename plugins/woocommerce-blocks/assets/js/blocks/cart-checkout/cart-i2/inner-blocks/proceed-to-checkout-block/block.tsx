/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CheckoutButton from '../../checkout-button';

const Block = ( {
	checkoutPageId,
}: {
	checkoutPageId: number;
} ): JSX.Element => {
	return (
		<CheckoutButton
			link={ getSetting( 'page-' + checkoutPageId, false ) }
		/>
	);
};

export default Block;
