/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '../settings/settings-slots';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );
const SiteVisibilitySlotFill = () => {
	return (
		<Fill>
			<div>Hello</div>
		</Fill>
	);
};

export const registerSiteAvailbilitySlotFill = () => {
	registerPlugin( 'woocommerce-admin-site-visibility-settings-slotfill', {
		scope: 'woocommerce-site-visibility-settings',
		render: SiteVisibilitySlotFill,
	} );
};
