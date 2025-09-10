/**
 * External dependencies
 */
import { __, isRTL } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './back-button.scss';
import { recordPaymentsEvent } from '~/settings-payments/utils';

interface BackButtonProps {
	/**
	 * The title of the back button.
	 */
	title: string;
	/**
	 * The URL to navigate to when the back button is clicked.
	 */
	href: string;
	/**
	 * The tooltip text of the back button.
	 */
	tooltipText?: string;
	/**
	 * If true, we will push into browser history instead of window.location.
	 */
	isRoute?: boolean;
	/**
	 * The identifier of the screen from which the user is navigating back (e.g., 'woopayments_payment_methods').
	 */
	from?: string;
}

/**
 * A button component that navigates to the specified URL or route when clicked.
 * It supports navigation using either `window.location.href` or browser history based on the `isRoute` prop.
 */
export const BackButton = ( {
	href,
	tooltipText = __( 'WooCommerce Settings', 'woocommerce' ),
	isRoute = false,
	from = '',
}: BackButtonProps ) => {
	const onGoBack = () => {
		// Record the event when the user clicks the button.
		recordPaymentsEvent( 'back_button_click', {
			from,
		} );

		if ( isRoute ) {
			const history = getHistory();
			history.push( href );
		} else {
			window.location.href = href;
		}
	};

	return (
		<Tooltip text={ tooltipText }>
			<Button
				className="woocommerce-settings-payments__back-button"
				icon={ isRTL() ? chevronRight : chevronLeft }
				onClick={ onGoBack }
				aria-label={ tooltipText }
			/>
		</Tooltip>
	);
};
