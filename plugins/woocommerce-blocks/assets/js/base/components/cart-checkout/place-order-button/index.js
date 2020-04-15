/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCheckoutContext } from '@woocommerce/base-context';
import { Icon, done } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import Button from '../button';

const PlaceOrderButton = () => {
	const {
		submitLabel,
		onSubmit,
		isCalculating,
		isBeforeProcessing,
		isProcessing,
		isAfterProcessing,
		isComplete,
		hasError,
	} = useCheckoutContext();

	const waitingForProcessing =
		isProcessing || isAfterProcessing || isBeforeProcessing;
	const waitingForRedirect = isComplete && ! hasError;
	const disabled =
		isCalculating || waitingForProcessing || waitingForRedirect;

	return (
		<Button
			className="wc-block-components-checkout-place-order-button"
			onClick={ onSubmit }
			disabled={ disabled }
			showSpinner={ waitingForProcessing }
		>
			{ waitingForRedirect ? (
				<Icon
					srcElement={ done }
					alt={ __( 'Done', 'woo-gutenberg-products-block' ) }
				/>
			) : (
				submitLabel
			) }
		</Button>
	);
};

export default PlaceOrderButton;
