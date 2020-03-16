/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import Button from '../button';

const PlaceOrderButton = () => {
	const { submitLabel, onSubmit } = useCheckoutContext();

	return (
		<Button
			className="wc-block-components-checkout-place-order-button"
			onClick={ onSubmit }
		>
			{ submitLabel }
		</Button>
	);
};

export default PlaceOrderButton;
