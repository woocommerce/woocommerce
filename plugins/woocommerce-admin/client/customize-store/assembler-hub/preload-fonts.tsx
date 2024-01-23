/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import { useSelect, useDispatch, resolveSelect } from '@wordpress/data';
import {
	privateApis as blockEditorPrivateApis,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import {
	FONT_PAIRINGS,
	FONT_TO_INSTALL,
} from './sidebar/global-styles/font-pairing-variations/constants';
import { FontFamiliesLoader } from './sidebar/global-styles/font-pairing-variations/font-families-loader';
import { useContext, useEffect } from '@wordpress/element';
import { Font } from './types/font';
import { FontFamiliesLoaderDotCom } from './sidebar/global-styles/font-pairing-variations/font-families-loader-dot-com';
import { CustomizeStoreContext } from '.';
import { isAIFlow, isNoAIFlow } from '../guards';

const { useGlobalSetting } = unlock( blockEditorPrivateApis );

let isFontEnabled = false;

export const PreloadFonts = () => {
	// @ts-ignore No types for this exist yet.
	const { __experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits } =
		useDispatch( coreStore );
	const [ enabledFontFamilies, setFontFamilies ]: [
		{
			custom: Array< Font >;
			theme: Array< Font >;
		},
		( font: { custom: Array< Font >; theme: Array< Font > } ) => void
	] = useGlobalSetting( 'typography.fontFamilies' );

	const { context } = useContext( CustomizeStoreContext );

	const { globalStylesId, installedFonts } = useSelect( ( select ) => {
		// @ts-ignore No types for this exist yet.
		const { __experimentalGetCurrentGlobalStylesId, getEntityRecords } =
			select( coreStore );
		return {
			globalStylesId: __experimentalGetCurrentGlobalStylesId(),
			installedFonts: getEntityRecords( 'postType', 'wp_font_family' ),
		};
	} );

	useEffect( () => {
		if (
			isFontEnabled ||
			installedFonts === null ||
			enabledFontFamilies === null
		) {
			return;
		}

		const { custom, theme } = enabledFontFamilies;

		const enabledFontSlugs = [
			...( custom ? custom.map( ( font ) => font.slug ) : [] ),
			...( theme ? theme.map( ( font ) => font.slug ) : [] ),
		];

		// @ts-expect-error -- Response from
		const fontsToEnable = installedFonts.reduce( ( acc, font ) => {
			if (
				enabledFontSlugs.includes( font.slug ) ||
				FONT_TO_INSTALL[ font.slug ] === undefined
			) {
				return acc;
			}

			return [
				...acc,
				{
					...JSON.parse( font.content.raw ),
				},
			];
		}, [] as Array< Record< string, Font > > );

		setFontFamilies( {
			...enabledFontFamilies,
			// @ts-ignore
			custom: [
				...( enabledFontFamilies.custom ?? [] ),
				...( fontsToEnable ?? [] ),
			],
		} );

		saveSpecifiedEntityEdits( 'root', 'globalStyles', globalStylesId, [
			'settings.typography.fontFamilies',
		] );

		isFontEnabled = true;
	}, [
		enabledFontFamilies,
		globalStylesId,
		installedFonts,
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
