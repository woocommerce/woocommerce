/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import {
	// @ts-expect-error No types for this exist yet.
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
// @ts-expect-error No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { useMemo } from '@wordpress/element';
import { FontFamily } from '../types/font';
import { FontFamiliesLoader } from './sidebar/global-styles/font-pairing-variations/font-families-loader';

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

	const iframeInstance = useMemo( () => {
		return document.querySelector(
			'.block-editor-block-preview__content iframe'
		) as HTMLObjectElement | null;
	}, [] );

	return (
		<>
			<FontFamiliesLoader
				fontFamilies={ [
					...( enabledFontFamilies.custom ?? [] ),
					...baseFontFamilies.theme,
				] }
				iframeInstance={ iframeInstance }
			/>
		</>
	);
};
