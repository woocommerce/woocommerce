/**
 * External dependencies
 */
import classnames from 'classnames';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { Icon, check } from '@wordpress/icons';
import Button from '@woocommerce/base-components/button';

interface PlaceOrderButton {
	label: string;
	fullWidth?: boolean | undefined;
}

const PlaceOrderButton = ( {
	label,
	fullWidth = false,
}: PlaceOrderButton ): JSX.Element => {
	const {
		onSubmit,
		isCalculating,
		isDisabled,
		waitingForProcessing,
		waitingForRedirect,
	} = useCheckoutSubmit();

	return (
		<>
			<Button
				className={ classnames(
					'wc-block-components-checkout-place-order-button',
					{
						'wc-block-components-checkout-place-order-button--full-width':
							fullWidth,
					}
				) }
				onClick={ onSubmit }
				disabled={
					isCalculating ||
					isDisabled ||
					waitingForProcessing ||
					waitingForRedirect
				}
				showSpinner={ waitingForProcessing }
			>
				{ waitingForRedirect ? <Icon icon={ check } /> : label }
			</Button>
		</>
	);
};

export default PlaceOrderButton;
