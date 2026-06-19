/**
 * External dependencies
 */
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { WooPaymentsExpressCheckoutSettings } from './express-checkout-settings';

const WooPaymentsExpressCheckoutSettingsRoute = () => {
	const { methodId = '' } = useParams();

	return <WooPaymentsExpressCheckoutSettings methodId={ methodId } />;
};

export { WooPaymentsExpressCheckoutSettings };
export default WooPaymentsExpressCheckoutSettingsRoute;
