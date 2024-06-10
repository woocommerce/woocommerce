/**
 * External dependencies
 */
import { render, createRoot, useContext } from '@wordpress/element';
import { createSlotFill, SlotFillProvider } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';

export const SETTINGS_SLOT_FILL_CONSTANT =
	'__EXPERIMENTAL__WcAdminSettingsSlots';

// @TODO: This needs to be exposed at @woocommerce/<something> so extensions can use it.
const { Slot } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const roots = {};

export const possiblyRenderSettingsSlots = (
	toggleSidebar,
	setSidebarContent
) => {
	//@TODO  We need to automatically register these based on the settings data so
	// this way extensions don't need to add to this configuration.
	const slots = [
		{
			id: 'wc_payments_settings_slotfill',
			scope: 'woocommerce-payments-settings',
		},
		{ id: 'wc_tax_settings_slotfill', scope: 'woocommerce-tax-settings' },
		{ id: 'wc_settings_slotfill', scope: 'woocommerce-settings' },
		{
			id: 'wc_site_visibility_settings_view',
			scope: 'woocommerce-settings',
		},
		{
			id: 'wc_settings_site_visibility_slotfill',
			scope: 'woocommerce-site-visibility-settings',
		},
	];

	slots.forEach( ( slot ) => {
		const slotDomElement = document.getElementById( slot.id );

		if ( ! slotDomElement ) {
			return;
		}

		const slotFill = (
			<>
				<SlotFillProvider>
					<Slot
						fillProps={ {
							toggleSidebar,
							setSidebarContent,
						} }
					/>
					<PluginArea scope={ slot.scope } />
				</SlotFillProvider>
			</>
		);

		if ( createRoot ) {
			const root = createRoot( slotDomElement );
			root.render( slotFill );
			roots[ slot.id ] = root;
		} else {
			render( slotFill, slotDomElement );
		}
	} );
};
