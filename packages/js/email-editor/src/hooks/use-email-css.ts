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
import { EmailStyles, storeName } from '../store';
import { useUserTheme } from './use-user-theme';
import { useGlobalStylesOutputWithConfig } from '../private-apis';

export function useEmailCss() {
	const { userTheme } = useUserTheme();
	const { editorTheme, layout, deviceType } = useSelect( ( select ) => {
		const {
			getEditorSettings,
			// @ts-expect-error getDeviceType is not in types.
			getDeviceType
		} = select( editorStore );

		const editorSettings = getEditorSettings();
		return {
			editorTheme: select( storeName ).getTheme(),
			// @ts-expect-error There are no types for the experimental features settings.
			// eslint-disable-next-line no-underscore-dangle
			layout: editorSettings.__experimentalFeatures?.layout,
			deviceType: getDeviceType()
		};
	}, [] );

	const mergedConfig = useMemo(
		() =>
			deepmerge.all( [
				{},
				editorTheme || {},
				userTheme || {},
			] ) as EmailStyles,
		[ editorTheme, userTheme ]
	);

	const [ styles ] = useGlobalStylesOutputWithConfig( mergedConfig );

	const finalStyles = useMemo(
		() => {
			return [
			...( ( styles as string[] ) ?? [] ),
			deviceType !== 'Mobile' && layout && {
				css: `.is-root-container{display:flow-root; width:${ layout?.contentSize }; margin: 0 auto;box-sizing: border-box;}`,
			},
		]},
		[ styles ]
	);

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return [ finalStyles || [] ];
}
