/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';
import { dispatch, resolveSelect } from '@wordpress/data';
import { Sender } from 'xstate';
// @ts-expect-error -- No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { mergeBaseAndUserConfigs } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
// @ts-expect-error -- No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { COLOR_PALETTES } from '../assembler-hub/sidebar/global-styles/color-palette-variations/constants';
import {
	FONT_PAIRINGS_WHEN_AI_IS_OFFLINE,
	FONT_PAIRINGS_WHEN_USER_DID_NOT_ALLOW_TRACKING,
} from '../assembler-hub/sidebar/global-styles/font-pairing-variations/constants';
import { updateTemplate } from '../data/actions';
import { THEME_SLUG } from '../data/constants';
import { HOMEPAGE_TEMPLATES } from '../data/homepageTemplates';
import { installAndActivateTheme as setTheme } from '../data/service';
import { trackEvent } from '../tracking';
import { DesignWithoutAIStateMachineContext, Theme } from './types';
import { installFontFamilies as installDefaultFontFamilies } from '../assembler-hub/utils/fonts';

const assembleSite = async () => {
	await updateTemplate( {
		homepageTemplateId: 'template1' as keyof typeof HOMEPAGE_TEMPLATES,
	} );
};

const browserPopstateHandler =
	() => ( sendBack: Sender< { type: 'EXTERNAL_URL_UPDATE' } > ) => {
		const popstateHandler = () => {
			sendBack( { type: 'EXTERNAL_URL_UPDATE' } );
		};
		window.addEventListener( 'popstate', popstateHandler );
		return () => {
			window.removeEventListener( 'popstate', popstateHandler );
		};
	};

const getActiveThemeWithRetries = async (): Promise< Theme[] | null > => {
	let retries = 3;

	while ( retries > 0 ) {
		const activeThemes = ( await resolveSelect( 'core' ).getEntityRecords(
			'root',
			'theme',
			{ status: 'active' },
			true
		) ) as Theme[];
		if ( activeThemes ) {
			return activeThemes;
		}

		retries--;
	}

	return null;
};

const getCurrentGlobalStylesId = async (): Promise< number | null > => {
	const activeThemes = await getActiveThemeWithRetries();
	if ( ! activeThemes ) {
		return null;
	}

	const currentThemeLinks = activeThemes[ 0 ]?._links;
	const url = currentThemeLinks?.[ 'wp:user-global-styles' ]?.[ 0 ]?.href;
	const globalStylesObject = ( await apiFetch( { url } ) ) as { id: number };

	return globalStylesObject.id;
};

const updateGlobalStylesWithDefaultValues = async (
	context: DesignWithoutAIStateMachineContext
) => {
	// We are using the first color palette and font pairing that are displayed on the color/font picker on the sidebar.
	const colorPalette = COLOR_PALETTES[ 0 ];

	const allowTracking =
		( await resolveSelect( OPTIONS_STORE_NAME ).getOption(
			'woocommerce_allow_tracking'
		) ) === 'yes';

	const fontPairing =
		context.isFontLibraryAvailable && allowTracking
			? FONT_PAIRINGS_WHEN_AI_IS_OFFLINE[ 0 ]
			: FONT_PAIRINGS_WHEN_USER_DID_NOT_ALLOW_TRACKING[ 0 ];

	const globalStylesId = await getCurrentGlobalStylesId();
	if ( ! globalStylesId ) {
		return;
	}

	// @ts-expect-error No types for this exist yet.
	const { saveEntityRecord } = dispatch( coreStore );

	await saveEntityRecord(
		'root',
		'globalStyles',
		{
			id: globalStylesId,
			styles: mergeBaseAndUserConfigs(
				colorPalette?.styles || {},
				fontPairing?.styles || {}
			),
			settings: mergeBaseAndUserConfigs(
				colorPalette?.settings || {},
				fontPairing?.settings || {}
			),
		},
		{
			throwOnError: true,
		}
	);
};

const updateShowOnFront = async () => {
	try {
		await apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				show_on_front: 'posts',
			},
		} );
	} catch ( error ) {
		throw error;
	}
};

const installAndActivateTheme = async (
	context: DesignWithoutAIStateMachineContext
) => {
	try {
		await setTheme( THEME_SLUG );
		await updateGlobalStylesWithDefaultValues( context );
	} catch ( error ) {
		trackEvent(
			'customize_your_store__no_ai_install_and_activate_theme_error',
			{
				theme: THEME_SLUG,
				error: error instanceof Error ? error.message : 'unknown',
			}
		);
		throw error;
	}
};

export const installPatterns = async () => {
	if ( ! window.wcAdminFeatures[ 'pattern-toolkit-full-composability' ] ) {
		return;
	}

	const isTrackingEnabled = window.wcTracks?.isEnabled || false;
	if ( ! isTrackingEnabled ) {
		return;
	}

	try {
		const { success } = await apiFetch< {
			success: boolean;
		} >( {
			path: '/wc/private/patterns',
			method: 'POST',
		} );

		if ( ! success ) {
			throw new Error( 'Fetching patterns failed' );
		}
	} catch ( error ) {
		throw error;
	}
};

const installFontFamilies = async () => {
	const isTrackingEnabled = window.wcTracks?.isEnabled || false;
	if ( ! isTrackingEnabled ) {
		return;
	}

	try {
		await installDefaultFontFamilies();
	} catch ( error ) {
		throw error;
	}
};

const createProducts = async () => {
	try {
		const { success } = await apiFetch< {
			success: boolean;
		} >( {
			path: `/wc-admin/onboarding/products`,
			method: 'POST',
		} );

		if ( ! success ) {
			throw new Error( 'Product creation failed' );
		}
	} catch ( error ) {
		throw error;
	}
};

export const enableTracking = async () => {
	try {
		await dispatch( OPTIONS_STORE_NAME ).updateOptions( {
			woocommerce_allow_tracking: 'yes',
		} );
		window.wcTracks.isEnabled = true;
	} catch ( error ) {
		throw error;
	}
};

export const services = {
	assembleSite,
	browserPopstateHandler,
	installAndActivateTheme,
	createProducts,
	installFontFamilies,
	installPatterns,
	updateGlobalStylesWithDefaultValues,
	enableTracking,
	updateShowOnFront,
};
