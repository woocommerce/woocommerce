/**
 * External dependencies
 */
import { useMemo, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import deepmerge from 'deepmerge';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import { EmailTheme, EmailBuiltStyles, storeName } from '../store';
import { useUserTheme } from './use-user-theme';
import { useGlobalStylesOutputWithConfig } from '../private-apis';
import { unwrapCompressedPresetStyleVariable } from '../style-variables';

// Empty array to avoid re-rendering the component when the array is empty
const EMPTY_ARRAY = [];

export function useEmailCss() {
	const stylesRef = useRef< EmailBuiltStyles[] >( EMPTY_ARRAY );
	const { userTheme } = useUserTheme();
	const { editorTheme, layout, deviceType, editorSettingsStyles } = useSelect(
		( select ) => {
			const {
				// @ts-expect-error getDeviceType is not in types.
				getDeviceType,
			} = select( editorStore );

			const editorSettings =
				select( storeName ).getInitialEditorSettings();

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
		rootContainerStyles += `padding-left:${ unwrapCompressedPresetStyleVariable(
			padding.left
		) };`;
		rootContainerStyles += `padding-right:${ unwrapCompressedPresetStyleVariable(
			padding.right
		) };`;
	}

	const finalStyles = [
		...( ( styles as EmailBuiltStyles[] ) ?? [] ),
		{
			css: `.is-root-container{ ${ rootContainerStyles } }`,
		},
		...( editorSettingsStyles ?? [] ),
	];

	if ( ! fastDeepEqual( stylesRef.current, finalStyles ) ) {
		stylesRef.current = finalStyles;
	}

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return [ stylesRef.current || EMPTY_ARRAY ];
}
