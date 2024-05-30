/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';
import { createSlotFill, SlotFillProvider } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';

export const SETTINGS_SLOT_FILL_CONSTANT =
	'__EXPERIMENTAL__WcAdminSettingsSlots';

const { Slot } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const roots = {};

export const possiblyRenderSettingsSlots = () => {
	const slots = [
		{
			id: 'wc_payments_settings_slotfill',
			scope: 'woocommerce-payments-settings',
		},
		{ id: 'wc_tax_settings_slotfill', scope: 'woocommerce-tax-settings' },
		{ id: 'wc_settings_slotfill', scope: 'woocommerce-settings' },
		{
			id: 'wc_settings_site_visibility_slotfill',
			scope: 'woocommerce-site-visibility-settings',
		},
	];

	slots.forEach( ( slot ) => {
		const slotDomElement = document.getElementById( slot.id );

		if ( slotDomElement ) {
			if ( createRoot ) {
				if ( roots[ slot.id ] ) {
					roots[ slot.id ].render(
						<>
							<SlotFillProvider>
								<Slot />
								<PluginArea scope={ slot.scope } />
							</SlotFillProvider>
						</>
					);
				} else {
					const root = createRoot( slotDomElement );
					root.render(
						<>
							<SlotFillProvider>
								<Slot />
								<PluginArea scope={ slot.scope } />
							</SlotFillProvider>
						</>
					);
					roots[ slot.id ] = root;
				}
			} else {
				render(
					<>
						<SlotFillProvider>
							<Slot />
							<PluginArea scope={ slot.scope } />
						</SlotFillProvider>
					</>,
					slotDomElement
				);
			}
		}
	} );
};
