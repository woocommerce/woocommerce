/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Button from '../button';

const PlaceOrderButton = ( { validateSubmit } ) => {
	const { submitLabel, onSubmit } = useCheckoutContext();

	return (
		<Button
			className="wc-block-components-checkout-place-order-button"
			onClick={ () => {
				const isValid = validateSubmit();
				if ( isValid ) {
					onSubmit();
				}
			} }
		>
			{ submitLabel }
		</Button>
	);
};

PlaceOrderButton.propTypes = {
	validateSubmit: PropTypes.func.isRequired,
};

export default PlaceOrderButton;
