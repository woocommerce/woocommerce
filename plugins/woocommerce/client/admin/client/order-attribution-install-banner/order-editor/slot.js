/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { createSlotFill, SlotFillProvider } from '@wordpress/components';
import { PluginArea, registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { OrderAttributionInstallBanner } from '../order-attribution-install-banner';
import {
	ORDER_ATTRIBUTION_INSTALL_BANNER_DOM_ID,
	ORDER_ATTRIBUTION_INSTALL_BANNER_SLOT_SCOPE,
	ORDER_ATTRIBUTION_SLOT_FILL_CONSTANT,
} from '../constants';

const { Slot, Fill } = createSlotFill( ORDER_ATTRIBUTION_SLOT_FILL_CONSTANT );

export const possiblyRenderOrderAttributionSlot = () => {
	const slotDomElement = document.getElementById(
		ORDER_ATTRIBUTION_INSTALL_BANNER_DOM_ID
	);

	if ( slotDomElement ) {
		createRoot( slotDomElement ).render(
			<>
				<SlotFillProvider>
					<Slot />
					<PluginArea
						scope={ ORDER_ATTRIBUTION_INSTALL_BANNER_SLOT_SCOPE }
					/>
				</SlotFillProvider>
			</>
		);
	}
};

const OrderAttributionInstallBannerSlotFill = () => {
	return (
		<Fill>
			<OrderAttributionInstallBanner bannerImage={ null } />
		</Fill>
	);
};

export const registerOrderAttributionSlotFill = () => {
	registerPlugin(
		'woocommerce-admin-order-editor-order-attribution-install-banner-slotfill',
		{
			scope: ORDER_ATTRIBUTION_INSTALL_BANNER_SLOT_SCOPE,
			render: OrderAttributionInstallBannerSlotFill,
		}
	);
};
