/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import Button from '../button';

const PlaceOrderButton = () => {
	const { submitLabel, onSubmit, isCalculating } = useCheckoutContext();

	return (
		<Button
			className="wc-block-components-checkout-place-order-button"
			onClick={ onSubmit }
			disabled={ isCalculating }
		>
			{ submitLabel }
		</Button>
	);
};

export default PlaceOrderButton;
