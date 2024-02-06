/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
// @ts-expect-error No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	privateApis as blockEditorPrivateApis,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';
// @ts-expect-error No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import {
	FONT_PAIRINGS,
	FONT_FAMILIES_TO_INSTALL,
} from './sidebar/global-styles/font-pairing-variations/constants';
import { FontFamiliesLoader } from './sidebar/global-styles/font-pairing-variations/font-families-loader';
import { useContext, useEffect, useMemo } from '@wordpress/element';
import { FontFace, FontFamily } from '../types/font';
import { FontFamiliesLoaderDotCom } from './sidebar/global-styles/font-pairing-variations/font-families-loader-dot-com';
import { CustomizeStoreContext } from '.';
import { isAIFlow, isNoAIFlow } from '../guards';

const { useGlobalSetting } = unlock( blockEditorPrivateApis );

let areFontsPreloaded = false;

export const PreloadFonts = () => {
	// @ts-expect-error No types for this exist yet.
	const { __experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits } =
		useDispatch( coreStore );
	const [ enabledFontFamilies, setFontFamilies ]: [
		{
			custom: Array< FontFamily >;
			theme: Array< FontFamily >;
		},
		( font: {
			custom: Array< FontFamily >;
			theme: Array< FontFamily >;
		} ) => void
	] = useGlobalSetting( 'typography.fontFamilies' );

	const { context } = useContext( CustomizeStoreContext );

	const { globalStylesId, installedFontFamilies } = useSelect( ( select ) => {
		// @ts-expect-error No types for this exist yet.
		const { __experimentalGetCurrentGlobalStylesId, getEntityRecords } =
			select( coreStore );
		return {
			globalStylesId: __experimentalGetCurrentGlobalStylesId(),
			installedFontFamilies: getEntityRecords(
				'postType',
				'wp_font_family',
				{ _embed: true }
			) as Array< {
				id: number;
				font_family_settings: FontFamily;
				_embedded: {
					font_faces: Array< {
						font_face_settings: FontFace;
					} >;
				};
			} >,
		};
	} );

	const parsedInstalledFontFamilies = useMemo( () => {
		return (
			( installedFontFamilies || [] ).map( ( fontFamilyPost ) => {
				return {
					id: fontFamilyPost.id,
					...fontFamilyPost.font_family_settings,
					fontFace:
						fontFamilyPost?._embedded?.font_faces.map(
							( face ) => face.font_face_settings
						) || [],
				};
			} ) || []
		);
	}, [ installedFontFamilies ] );

	useEffect( () => {
		if (
			areFontsPreloaded ||
			installedFontFamilies === null ||
			enabledFontFamilies === null
		) {
			return;
		}

		const { custom, theme } = enabledFontFamilies;

		const enabledFontSlugs = [
			...( custom ? custom.map( ( font ) => font.slug ) : [] ),
			...( theme ? theme.map( ( font ) => font.slug ) : [] ),
		];

		const fontFamiliesToEnable = parsedInstalledFontFamilies.reduce(
			( acc, font ) => {
				if (
					enabledFontSlugs.includes( font.slug ) ||
					FONT_FAMILIES_TO_INSTALL[ font.slug ] === undefined
				) {
					return acc;
				}

				return [
					...acc,
					{
						...font,
					},
				];
			},
			[] as Array< FontFamily >
		);

		setFontFamilies( {
			...enabledFontFamilies,
			custom: [
				...( enabledFontFamilies.custom ?? [] ),
				...( fontFamiliesToEnable ?? [] ),
			],
		} );

		saveSpecifiedEntityEdits( 'root', 'globalStyles', globalStylesId, [
			'settings.typography.fontFamilies',
		] );

		areFontsPreloaded = true;
	}, [
		enabledFontFamilies,
		globalStylesId,
		installedFontFamilies,
		parsedInstalledFontFamilies,
		saveSpecifiedEntityEdits,
		setFontFamilies,
	] );

	const allFontChoices = FONT_PAIRINGS.map(
		( fontPair ) => fontPair?.settings?.typography?.fontFamilies?.theme
	);

	const fontFamilies = allFontChoices.map( ( fontPair ) => {
		return [
			...fontPair.map( ( font ) => {
				return {
					...font,
					name: font.fontFamily,
				};
			} ),
		];
	} );

	return (
		<>
			{ isAIFlow( context.flowType ) &&
				fontFamilies.map( ( fontPair ) => (
					<FontFamiliesLoaderDotCom
						fontFamilies={ fontPair }
						key={ fontPair
							.map( ( font ) => font.slug )
							.join( '-' ) }
						preload
					/>
				) ) }
			{ isNoAIFlow( context.flowType ) && (
				<FontFamiliesLoader
					fontFamilies={ enabledFontFamilies.custom }
				/>
			) }
		</>
	);
};
