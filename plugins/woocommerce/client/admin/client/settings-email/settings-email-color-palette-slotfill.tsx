/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import './style.scss';
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import { ResetStylesControl } from './settings-email-color-palette-control';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

export type DefaultColors = {
	baseColor: string;
	bgColor: string;
	bodyBgColor: string;
	bodyTextColor: string;
	footerTextColor: string;
};

type EmailColorPaletteFillProps = {
	defaultColors: DefaultColors;
	hasThemeJson: boolean;
	autoSync: boolean;
	autoSyncInput: HTMLInputElement;
};

const EmailColorPaletteFill: React.FC< EmailColorPaletteFillProps > = ( {
	defaultColors,
	hasThemeJson,
	autoSync,
	autoSyncInput,
} ) => {
	return (
		<Fill>
			<ResetStylesControl
				autoSync={ autoSync }
				autoSyncInput={ autoSyncInput }
				defaultColors={ defaultColors }
				hasThemeJson={ hasThemeJson }
			/>
		</Fill>
	);
};

export const registerSettingsEmailColorPaletteFill = () => {
	const autoSyncInput = document.getElementById(
		'woocommerce_email_auto_sync_with_theme'
	) as HTMLInputElement;
	if ( ! autoSyncInput ) {
		return; // Don't register the plugin if the input doesn't exist
	}
	const autoSync = autoSyncInput.value === 'yes';
	const slotElementId = 'wc_settings_email_color_palette_slotfill';
	const slotElement = document.getElementById( slotElementId );

	const defaultColorsData = slotElement?.getAttribute(
		'data-default-colors'
	);
	let defaultColors = {} as DefaultColors;
	try {
		const {
			base_color_default: baseColor,
			bg_color_default: bgColor,
			body_bg_color_default: bodyBgColor,
			body_text_color_default: bodyTextColor,
			footer_text_color_default: footerTextColor,
		} = JSON.parse( defaultColorsData || '' );
		defaultColors = {
			baseColor,
			bgColor,
			bodyBgColor,
			bodyTextColor,
			footerTextColor,
		};
	} catch ( e ) {}

	const hasThemeJson =
		slotElement?.getAttribute( 'data-has-theme-json' ) !== null;

	registerPlugin( 'woocommerce-admin-settings-email-color-palette', {
		scope: 'woocommerce-email-color-palette-settings',
		render: () => (
			<EmailColorPaletteFill
				autoSync={ autoSync }
				autoSyncInput={ autoSyncInput }
				defaultColors={ defaultColors }
				hasThemeJson={ hasThemeJson }
			/>
		),
	} );
};
