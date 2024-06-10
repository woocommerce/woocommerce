/**
 * External dependencies
 */
import { createSlotFill, Button } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from './settings-slots';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const ExampleSettingsViewSlotFill = () => {
	const style = { margin: '36px 0px' };
	return (
		<Fill>
			{ ( { toggleSidebar, setSidebarContent } ) => {
				return (
					<div style={ style }>
						<h1>Example Settings View</h1>
						<p>This is the main content using a SlotFill</p>
						<Button
							variant="secondary"
							onClick={ () => {
								toggleSidebar();
								setSidebarContent(
									<h2>This is content created by the Fill</h2>
								);
							} }
						>
							Show Sidebar
						</Button>
					</div>
				);
			} }
		</Fill>
	);
};

export const registerExampleSettingsView = () => {
	registerPlugin( 'woocommerce-exampple-settings-view-slotfill', {
		scope: 'woocommerce-settings',
		render: ExampleSettingsViewSlotFill,
	} );
};
