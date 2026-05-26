/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin, getPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { PaymentsBannerWrapper } from './payment-settings-banner';
import { SETTINGS_SLOT_FILL_CONSTANT } from '../settings/settings-slots';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );
const PLUGIN_ID = 'woocommerce-admin-paymentsgateways-settings-banner';

const PaymentsBannerFill = () => {
	return (
		<Fill>
			<PaymentsBannerWrapper />
		</Fill>
	);
};

export const registerPaymentsSettingsBannerFill = () => {
	if ( getPlugin( PLUGIN_ID ) ) {
		return;
	}

	registerPlugin( PLUGIN_ID, {
		scope: 'woocommerce-payments-settings',
		render: PaymentsBannerFill,
	} );
};
