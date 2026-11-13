/**
 * External dependencies
 */
import { type ReactNode } from 'react';
import { __, isRTL } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import clsx from 'clsx';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './back-button.scss';
import { recordPaymentsEvent } from '~/settings-payments/utils';

interface BackButtonProps {
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
	/**
	 * Visible label rendered next to the chevron, inside the button. Supplying
	 * it makes the whole label part of the click target, and the button takes
	 * its accessible name from the label rather than from `tooltipText`, so the
	 * name always matches what is on screen.
	 */
	children?: ReactNode;
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
	children,
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
				// Button only sets its own has-text when the children are a
				// plain string, so carry the distinction explicitly.
				className={ clsx(
					'woocommerce-settings-payments__back-button',
					{
						'woocommerce-settings-payments__back-button--with-label':
							!! children,
					}
				) }
				icon={ isRTL() ? chevronRight : chevronLeft }
				onClick={ onGoBack }
				// Without a visible label the chevron alone carries no name, so
				// the tooltip text has to supply one. With a label, overriding
				// the name would hide the on-screen text from it.
				aria-label={ children ? undefined : tooltipText }
			>
				{ children }
			</Button>
		</Tooltip>
	);
};
