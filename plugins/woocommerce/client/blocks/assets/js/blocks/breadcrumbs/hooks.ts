/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

type EditorSettingsWithGlobalStyles = Record< string | symbol, unknown > & {
	blocks?: Record<
		string,
		{
			typography?: {
				fontSize?: string;
			};
		}
	>;
};

/**
 * Returns the theme.json font size for the Store Breadcrumbs block.
 */
export function useBreadcrumbsThemeFontSize(): string | undefined {
	return useSelect( ( select ) => {
		const settings = select(
			blockEditorStore
		).getSettings() as unknown as Record< string | symbol, unknown >;

		const globalStylesKey = Object.getOwnPropertySymbols( settings ).find(
			( key ) => key.description === 'globalStylesDataKey'
		);

		if ( ! globalStylesKey ) {
			return undefined;
		}

		const globalStyles = settings[
			globalStylesKey
		] as EditorSettingsWithGlobalStyles;

		return globalStyles?.blocks?.[ 'woocommerce/breadcrumbs' ]?.typography
			?.fontSize;
	}, [] );
}
