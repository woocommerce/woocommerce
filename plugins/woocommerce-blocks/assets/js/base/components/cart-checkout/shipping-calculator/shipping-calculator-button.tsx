/**
 * External dependencies
 */
import { Button } from '@ariakit/react';
import { __ } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ShippingCalculatorContext } from './context';
import './style.scss';

export const ShippingCalculatorButton = ( {
	label = __( 'Calculate', 'woocommerce' ),
}: {
	label?: string;
} ): JSX.Element | null => {
	const {
		isShippingCalculatorOpen,
		setIsShippingCalculatorOpen,
		showCalculator,
		shippingCalculatorId,
	} = useContext( ShippingCalculatorContext );

	if ( ! showCalculator ) {
		return null;
	}

	return (
		<Button
			render={ <span /> }
			className="wc-block-components-totals-shipping__change-address__link"
			id="wc-block-components-totals-shipping__change-address__link"
			onClick={ ( e ) => {
				e.preventDefault();
				setIsShippingCalculatorOpen( ! isShippingCalculatorOpen );
			} }
			aria-label={ label }
			aria-expanded={ isShippingCalculatorOpen }
			aria-controls={ shippingCalculatorId }
		>
			{ label }
		</Button>
	);
};

export default ShippingCalculatorButton;
