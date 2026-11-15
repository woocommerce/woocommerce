/**
 * External dependencies
 */
import { act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { possiblyRenderSettingsSlots } from '../settings-slots';

// Stand in for the real PluginArea so the scope each mount is rendered with is
// readable from the DOM.
jest.mock( '@wordpress/plugins', () => ( {
	PluginArea: ( { scope }: { scope: string } ) => (
		<div data-testid="plugin-area" data-scope={ scope } />
	),
} ) );

const renderSlot = async ( slotElementId: string ) => {
	const mount = document.createElement( 'div' );
	mount.id = slotElementId;
	document.body.appendChild( mount );

	// possiblyRenderSettingsSlots mounts its own React root, which Testing
	// Library does not wrap for us.
	// eslint-disable-next-line testing-library/no-unnecessary-act
	await act( async () => {
		possiblyRenderSettingsSlots();
	} );

	return mount;
};

describe( 'possiblyRenderSettingsSlots', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'renders the email color palette mount in the palette fill scope', async () => {
		const mount = await renderSlot(
			'wc_settings_email_color_palette_slotfill'
		);

		const pluginArea = mount.querySelector( '[data-testid="plugin-area"]' );
		// settings-email-color-palette-slotfill.tsx registers its plugin with
		// this exact scope, and its own test pins the same literal, so a rename
		// on either side fails.
		expect( pluginArea?.getAttribute( 'data-scope' ) ).toBe(
			'woocommerce-email-color-palette-settings'
		);
	} );

	it( 'renders nothing into a mount whose id is not a settings slot', () => {
		const mount = document.createElement( 'div' );
		mount.id = 'wc_settings_unknown_slotfill';
		document.body.appendChild( mount );

		possiblyRenderSettingsSlots();

		expect(
			mount.querySelector( '[data-testid="plugin-area"]' )
		).toBeNull();
	} );
} );
