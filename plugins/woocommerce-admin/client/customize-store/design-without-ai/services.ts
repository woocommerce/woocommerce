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
import { FontFace, FontFamily } from '../types/font';
import {
	FontCollectionResponse,
	installFontFace,
	installFontFamily,
	getFontFamiliesAndFontFaceToInstall,
	FontCollectionsResponse,
} from './fonts';

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

const installFontFamilies = async () => {
	try {
		const installedFontFamily = ( await resolveSelect(
			'core'
		).getEntityRecords( 'postType', 'wp_font_family', {
			per_page: -1,
		} ) ) as Array< {
			id: number;
			font_faces: Array< number >;
			font_family_settings: FontFamily;
		} >;

		const installedFontFamiliesWithFontFaces = await Promise.all(
			installedFontFamily.map( async ( fontFamily ) => {
				const fontFaces = await apiFetch< Array< FontFace > >( {
					path: `/wp/v2/font-families/${ fontFamily.id }/font-faces`,
					method: 'GET',
				} );

				return {
					...fontFamily,
					font_face: fontFaces,
				};
			} )
		);

		const fontCollections = await apiFetch< FontCollectionsResponse >( {
			path: '/wp/v2/font-collections?_fields=slug,name,description,id',
			method: 'GET',
		} );

		const fontCollection = await apiFetch< FontCollectionResponse >( {
			path: `/wp/v2/font-collections/${ fontCollections[ 0 ].slug }`,
			method: 'GET',
		} );

		const { fontFacesToInstall, fontFamiliesWithFontFacesToInstall } =
			getFontFamiliesAndFontFaceToInstall(
				fontCollection,
				installedFontFamiliesWithFontFaces
			);

		const fontFamiliesWithFontFaceToInstallPromises =
			fontFamiliesWithFontFacesToInstall.map( async ( fontFamily ) => {
				const fontFamilyResponse = await installFontFamily(
					fontFamily
				);
				return Promise.all(
					fontFamily.fontFace.map( async ( fontFace, index ) => {
						installFontFace(
							{
								...fontFace,
								fontFamilyId: fontFamilyResponse.id,
							},
							index
						);
					} )
				);
			} );

		const fontFacesToInstallPromises =
			fontFacesToInstall.map( installFontFace );

		await Promise.all( [
			...fontFamiliesWithFontFaceToInstallPromises,
			...fontFacesToInstallPromises,
		] );
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

export const services = {
	assembleSite,
	browserPopstateHandler,
	installAndActivateTheme,
	createProducts,
	installFontFamilies,
};
