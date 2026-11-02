/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { registerSettingsEmailColorPaletteFill } from '../settings-email-color-palette-slotfill';

jest.mock( '@wordpress/plugins', () => ( {
	registerPlugin: jest.fn(),
} ) );

const registerPluginMock = registerPlugin as jest.Mock;

// The attribute shape WC_Settings_Emails::email_color_palette() prints.
const phpDefaultColors = {
	base: '#720eec',
	bg: '#f7f7f7',
	body_bg: '#ffffff',
	body_text: '#1e1e1e',
	footer_text: '#787c82',
};

const renderMount = ( hasThemeJson: boolean, autoSync: string ) => {
	document.body.innerHTML = `
		<div
			id="wc_settings_email_color_palette_slotfill"
			data-default-colors='${ JSON.stringify( phpDefaultColors ) }'
			${ hasThemeJson ? 'data-has-theme-json' : '' }
		></div>
		<input type="hidden" id="woocommerce_email_auto_sync_with_theme" value="${ autoSync }" />
	`;
};

const renderedFillProps = () => {
	expect( registerPluginMock ).toHaveBeenCalledTimes( 1 );
	const [ , settings ] = registerPluginMock.mock.calls[ 0 ];
	return settings.render().props;
};

describe( 'registerSettingsEmailColorPaletteFill', () => {
	afterEach( () => {
		document.body.innerHTML = '';
		registerPluginMock.mockClear();
	} );

	it.each( [
		[ 'with', true, 'yes' ],
		[ 'without', false, 'no' ],
	] )(
		'reads the PHP mount attributes %s theme.json',
		( _label, hasThemeJson, autoSync ) => {
			renderMount( hasThemeJson, autoSync );

			registerSettingsEmailColorPaletteFill();

			expect( renderedFillProps() ).toMatchObject( {
				autoSync: autoSync === 'yes',
				defaultColors: {
					baseColor: '#720eec',
					bgColor: '#f7f7f7',
					bodyBgColor: '#ffffff',
					bodyTextColor: '#1e1e1e',
					footerTextColor: '#787c82',
				},
				hasThemeJson,
			} );
		}
	);

	it( 'registers the fill in the scope the settings slot renders', () => {
		renderMount( true, 'no' );

		registerSettingsEmailColorPaletteFill();

		expect( registerPluginMock ).toHaveBeenCalledTimes( 1 );
		const [ name, settings ] = registerPluginMock.mock.calls[ 0 ];
		expect( name ).toBe( 'woocommerce-admin-settings-email-color-palette' );
		// settings-slots.js renders a PluginArea with this exact scope for the
		// wc_settings_email_color_palette_slotfill mount, and its own test pins
		// the same literal, so a rename on either side fails.
		expect( settings.scope ).toBe(
			'woocommerce-email-color-palette-settings'
		);
	} );

	it( 'does not register the fill without the auto-sync input', () => {
		document.body.innerHTML =
			'<div id="wc_settings_email_color_palette_slotfill"></div>';

		registerSettingsEmailColorPaletteFill();

		expect( registerPluginMock ).not.toHaveBeenCalled();
	} );
} );
