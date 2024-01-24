/**
 * External dependencies
 */
import { Sender } from 'xstate';
import { recordEvent } from '@woocommerce/tracks';
import apiFetch from '@wordpress/api-fetch';
import { resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { updateTemplate } from '../data/actions';
import { HOMEPAGE_TEMPLATES } from '../data/homepageTemplates';
import { installAndActivateTheme as setTheme } from '../data/service';
import { THEME_SLUG } from '../data/constants';
import { FONT_TO_INSTALL } from '../assembler-hub/sidebar/global-styles/font-pairing-variations/constants';
import { Font } from '../assembler-hub/types/font';

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

const installAndActivateTheme = async () => {
	try {
		await setTheme( THEME_SLUG );
	} catch ( error ) {
		recordEvent(
			'customize_your_store__no_ai_install_and_activate_theme_error',
			{
				theme: THEME_SLUG,
				error: error instanceof Error ? error.message : 'unknown',
			}
		);
		throw error;
	}
};

const installFont = ( data: Font ) => {
	const config = {
		path: '/wp/v2/font-families',
		method: 'POST',
		data: {
			font_family_settings: JSON.stringify( data ),
		},
	};
	return apiFetch( config );
};

type FontCollectionResponse = Array< {
	data: {
		fontFamilies: Array< Font >;
	};
} >;

const getFontToInstall = (
	fontCollection: FontCollectionResponse,
	installedFonts: Array< Font >
) => {
	const slugInstalledFonts = installedFonts.map( ( { slug } ) => slug );

	return Object.entries( FONT_TO_INSTALL ).reduce(
		( acc, [ slug, fontData ] ) => {
			if ( slugInstalledFonts.includes( slug ) ) {
				return acc;
			}

			const fontFromCollection =
				fontCollection[ 0 ].data.fontFamilies.find(
					( { slug: fontSlug } ) => fontSlug === slug
				);

			if ( ! fontFromCollection ) {
				return acc;
			}

			const fontFace = fontFromCollection.fontFace.filter(
				( { fontWeight } ) =>
					fontData.fontWeights.includes( fontWeight )
			);

			return [
				...acc,
				{
					...fontFromCollection,
					fontFace,
				},
			];
		},
		[] as Array< Font >
	);
};

const installFonts = async () => {
	const config = {
		path: '/wp/v2/font-collections',
		method: 'GET',
	};

	const installedFonts = ( await resolveSelect( 'core' ).getEntityRecords(
		'postType',
		'wp_font_family',
		{
			per_page: -1,
		}
	) ) as Array< Font >;

	const fontCollection = await apiFetch< FontCollectionResponse >( config );

	const fontToInstall = getFontToInstall( fontCollection, installedFonts );

	await Promise.all( fontToInstall.map( installFont ) );
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

export const services = {
	assembleSite,
	browserPopstateHandler,
	installAndActivateTheme,
	createProducts,
	installFonts,
};
