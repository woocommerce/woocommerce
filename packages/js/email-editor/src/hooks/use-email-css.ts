/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import deepmerge from 'deepmerge';

/**
 * Internal dependencies
 */
import { EmailTheme, EmailBuiltStyles, storeName } from '../store';
import { useUserTheme } from './use-user-theme';
import { useGlobalStylesOutputWithConfig } from '../private-apis';

// Utility to normalize spacing value to --wp--preset--spacing--<value> format
function normalizeSpacingValue( value: string ): string {
	if ( typeof value !== 'string' ) return value;
	const match = value.match( /^var:preset\|spacing\|([a-zA-Z0-9-]+)$/ );
	return match ? `var(--wp--preset--spacing--${ match[ 1 ] })` : value;
}

export function useEmailCss() {
	const { userTheme } = useUserTheme();
	const { editorTheme, layout, deviceType, editorSettingsStyles } = useSelect(
		( select ) => {
			const {
				getEditorSettings,
				// @ts-expect-error getDeviceType is not in types.
				getDeviceType,
			} = select( editorStore );

			const editorSettings = getEditorSettings();

			return {
				editorTheme: select( storeName ).getTheme(),
				// @ts-expect-error There are no types for the experimental features settings.
				// eslint-disable-next-line no-underscore-dangle
				layout: editorSettings.__experimentalFeatures?.layout,
				deviceType: getDeviceType(),
				editorSettingsStyles: editorSettings.styles,
			};
		},
		[]
	);

	const mergedConfig = useMemo(
		() =>
			deepmerge.all( [
				{},
				editorTheme || {},
				userTheme || {},
			] ) as EmailTheme,
		[ editorTheme, userTheme ]
	);

	const [ styles ] = useGlobalStylesOutputWithConfig( mergedConfig );

	let rootContainerStyles = '';
	if ( layout && deviceType !== 'Mobile' ) {
		rootContainerStyles = `display:flow-root; width:${ layout?.contentSize }; margin: 0 auto;box-sizing: border-box;`;
	}
	const padding = mergedConfig.styles?.spacing?.padding;
	if ( padding ) {
		rootContainerStyles += `padding-left:${ normalizeSpacingValue(
			padding.left
		) };`;
		rootContainerStyles += `padding-right:${ normalizeSpacingValue(
			padding.right
		) };`;
	}

	const finalStyles = useMemo( () => {
		return [
			...( ( styles as EmailBuiltStyles[] ) ?? [] ),
			{
				css: `.is-root-container{ ${ rootContainerStyles } }`,
			},
			...( editorSettingsStyles ?? [] ),
		];
	}, [ styles, editorSettingsStyles, rootContainerStyles ] );

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return [ finalStyles || [] ];
}
