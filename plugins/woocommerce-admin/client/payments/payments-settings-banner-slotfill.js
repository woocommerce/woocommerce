/**
 * External dependencies
 */
import { createSlotFill, SlotFillProvider } from '@wordpress/components';
import { registerPlugin, PluginArea } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { PaymentsBannerWrapper } from './payment-settings-banner';

const { Fill, Slot } = createSlotFill(
	'__EXPERIMENTAL__WcAdminPaymentsGatewaysSettingsBanner'
);
const PaymentsBannerFill = () => {
	return (
		<Fill>
			<PaymentsBannerWrapper />
		</Fill>
	);
};

export const WcAdminPaymentsGatewaysBannerSlot = () => {
	return (
		<>
			<SlotFillProvider>
				<Slot />
				<PluginArea scope="woocommerce-settings" />
			</SlotFillProvider>
		</>
	);
};

registerPlugin( 'woocommerce-admin-paymentsgateways-settings-banner', {
	scope: 'woocommerce-settings',
	render: PaymentsBannerFill,
} );
