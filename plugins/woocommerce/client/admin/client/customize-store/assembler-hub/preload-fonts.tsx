/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
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
import { FONT_PAIRINGS } from './sidebar/global-styles/font-pairing-variations/constants';
import { FontFamiliesLoader } from './sidebar/global-styles/font-pairing-variations/font-families-loader';
import { useContext, useMemo } from '@wordpress/element';
import { FontFamily } from '../types/font';
import { FontFamiliesLoaderDotCom } from './sidebar/global-styles/font-pairing-variations/font-families-loader-dot-com';
import { CustomizeStoreContext } from '.';
import { isAIFlow, isNoAIFlow } from '../guards';

const { useGlobalSetting } = unlock( blockEditorPrivateApis );

export const PreloadFonts = () => {
	const [ enabledFontFamilies ]: [
		{
			custom: Array< FontFamily >;
			theme: Array< FontFamily >;
		},
		( font: {
			custom: Array< FontFamily >;
			theme: Array< FontFamily >;
		} ) => void
	] = useGlobalSetting( 'typography.fontFamilies' );

	// theme.json file font families
	const [ baseFontFamilies ] = useGlobalSetting(
		'typography.fontFamilies',
		undefined,
		'base'
	);

	const { context } = useContext( CustomizeStoreContext );

	const allFontChoices = FONT_PAIRINGS.map(
		( fontPair ) => fontPair?.settings?.typography?.fontFamilies?.theme
	);

	const iframeInstance = useMemo( () => {
		return document.querySelector(
			'.block-editor-block-preview__content iframe'
		) as HTMLObjectElement | null;
	}, [] );

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
					fontFamilies={ [
						...( enabledFontFamilies.custom ?? [] ),
						...baseFontFamilies.theme,
					] }
					iframeInstance={ iframeInstance }
				/>
			) }
		</>
	);
};
