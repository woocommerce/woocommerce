/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { Panel } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import { ShippingCalculatorContext } from './context';
import './style.scss';
import ShippingCalculator from './shipping-calculator';

type ShippingCalculatorPanelProps = {
	title: string | React.ReactNode;
};

export const ShippingCalculatorPanel = ( {
	title,
}: ShippingCalculatorPanelProps ) => {
	const { isShippingCalculatorOpen, setIsShippingCalculatorOpen } =
		useContext( ShippingCalculatorContext );
	return (
		<Panel
			className="wc-block-components-totals-shipping-panel"
			initialOpen={ false }
			hasBorder={ false }
			title={ title }
			state={ [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] }
		>
			<ShippingCalculator />
		</Panel>
	);
};
