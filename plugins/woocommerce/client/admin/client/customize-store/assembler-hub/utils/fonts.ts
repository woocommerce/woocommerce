/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	FontFace,
	FontFamiliesToInstall,
	FontFamily,
} from '~/customize-store/types/font';
import { FONT_FAMILIES_TO_INSTALL } from '../sidebar/global-styles/font-pairing-variations/constants';

export type FontCollectionsResponse = Array< {
	slug: string;
	description: string;
	name: string;
} >;

export type FontCollectionResponse = {
	slug: string;
	name: string;
	font_families: Array< {
		font_family_settings: FontFamily;
		categories: Array< string >;
	} >;
};

const getInstalledFontFamilyByNameFontFamily = (
	installedFontFamilies: Array< {
		id: number;
		font_family_settings: FontFamily;
		font_face: Array< FontFace >;
	} >,
	nameFontFamily: string
) => {
	return installedFontFamilies.find(
		( { font_family_settings } ) =>
			font_family_settings.slug === nameFontFamily
	);
};

const getFontFamiliesToInstall = (
	fontCollection: FontCollectionResponse,
	slugFontFamily: string,
	fontFamilyToInstall: FontFamiliesToInstall[ 'slug' ]
) => {
	const fontFromCollection = fontCollection.font_families.find(
		( { font_family_settings } ) =>
			font_family_settings.slug === slugFontFamily
	);

	if ( ! fontFromCollection ) {
		return null;
	}

	const fontFace = fontFromCollection?.font_family_settings.fontFace.filter(
		( { fontWeight } ) =>
			fontFamilyToInstall.fontWeights.includes( fontWeight )
	);

	const fontFamilyWithFontFace = {
		...fontFromCollection?.font_family_settings,
		fontFace,
	};

	return fontFamilyWithFontFace;
};

/**
 * Retrieves font families and font faces to install based on a provided font collection and a list of installed font families.
 * The fontFamilyWithFontFaceToInstall include fontFamilies with font faces that are not installed yet.
 * The fontFaceToInstall include font faces that are not installed yet, but already have the font family installed.
 *
 * @param fontCollection        - The complete font collection containing all available font data.
 * @param installedFontFamilies - An array of installed font families with associated font faces and settings.
 * @return An object containing font families with font faces to install and individual font faces to install.
 */
export const getFontFamiliesAndFontFaceToInstall = (
	fontCollection: FontCollectionResponse,
	installedFontFamilies: Array< {
		id: number;
		font_face: Array< FontFace >;
		font_family_settings: FontFamily;
	} >
) => {
	return Object.entries( FONT_FAMILIES_TO_INSTALL ).reduce(
		( acc, [ slug, fontData ] ) => {
			const fontFamilyWithFontFaceToInstall = getFontFamiliesToInstall(
				fontCollection,
				slug,
				fontData
			);

			if ( ! fontFamilyWithFontFaceToInstall ) {
				return acc;
			}

			const fontFamily = getInstalledFontFamilyByNameFontFamily(
				installedFontFamilies,
				fontFamilyWithFontFaceToInstall.slug
			);

			if ( ! fontFamily ) {
				return {
					...acc,
					fontFamiliesWithFontFacesToInstall: [
						...acc.fontFamiliesWithFontFacesToInstall,
						fontFamilyWithFontFaceToInstall,
					],
				};
			}

			const fontFace = fontFamily.font_face.filter( ( { fontWeight } ) =>
				fontData.fontWeights.includes( fontWeight )
			);

			return {
				...acc,
				fontFacesToInstall: [
					...acc.fontFacesToInstall,
					...fontFace.map( ( face ) => ( {
						...face,
						fontFamilyId: fontFamily.id,
					} ) ),
				],
			};
		},
		{
			fontFamiliesWithFontFacesToInstall: [],
			fontFacesToInstall: [],
		} as {
			fontFamiliesWithFontFacesToInstall: Array< FontFamily >;
			fontFacesToInstall: Array<
				FontFace & {
					fontFamilyId: number;
				}
			>;
		}
	);
};

export const installFontFamily = ( data: FontFamily ) => {
	const config = {
		path: '/wp/v2/font-families',
		method: 'POST',
		data: {
			font_family_settings: JSON.stringify( {
				name: data.name,
				slug: data.slug,
				fontFamily: data.fontFamily,
				preview: data.preview,
			} ),
		},
	};

	return apiFetch< {
		id: number;
		font_family_settings: string;
	} >( config );
};

async function downloadFontFaceAssets( src: string ) {
	try {
		const fontBlob = await ( await fetch( new Request( src ) ) ).blob();
		const fileName = src.split( '/' ).pop() as string;
		return new File( [ fontBlob ], fileName, {
			type: fontBlob.type,
		} );
	} catch ( error ) {
		throw new Error( `Error downloading font face asset from ${ src }` );
	}
}

function makeFontFacesFormData(
	fontFaceFile: File,
	formData: FormData,
	index: number
) {
	const fileId = `file-${ index }`;
	formData.append( fileId, fontFaceFile, fontFaceFile.name );
	return fileId;
}

export const installFontFace = async (
	data: FontFace & {
		fontFamilyId: number;
	},
	index: number
) => {
	const { fontFamilyId, ...font } = data;
	const fontFaceAssets = await downloadFontFaceAssets(
		Array.isArray( font.src ) ? font.src[ 0 ] : font.src
	);
	const formData = new FormData();

	const fontFile = await makeFontFacesFormData(
		fontFaceAssets,
		formData,
		index
	);

	formData.append(
		'font_face_settings',
		JSON.stringify( { ...font, src: fontFile } )
	);
	const config = {
		path: `/wp/v2/font-families/${ data.fontFamilyId }/font-faces/`,
		method: 'POST',
		body: formData,
	};

	return apiFetch( config );
};

export const installFontFamilies = async () => {
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

	const fontCollection = await apiFetch< FontCollectionResponse >( {
		path: `/wp/v2/font-collections/google-fonts`,
		method: 'GET',
	} );

	const { fontFacesToInstall, fontFamiliesWithFontFacesToInstall } =
		getFontFamiliesAndFontFaceToInstall(
			fontCollection,
			installedFontFamiliesWithFontFaces
		);

	const fontFamiliesWithFontFaceToInstallPromises =
		fontFamiliesWithFontFacesToInstall.map( async ( fontFamily ) => {
			const fontFamilyResponse = await installFontFamily( fontFamily );
			return Promise.all(
				fontFamily.fontFace.map( async ( fontFace, index ) => {
					return installFontFace(
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

	return ( await Promise.all( [
		...fontFamiliesWithFontFaceToInstallPromises,
		...fontFacesToInstallPromises,
	] ) ) as Array<
		Array< {
			font_face_settings: FontFace;
		} >
	>;
};
