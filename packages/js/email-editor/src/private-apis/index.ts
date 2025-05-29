/**
 * External dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';
import {
	// @ts-expect-error No types for privateApis.
	privateApis as editorPrivateApis,
} from '@wordpress/editor';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error No types for privateApis.
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';

const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
	'@wordpress/edit-site' // The module name must be in the list of allowed, so for now I used the package name of the post editor
);

/**
 * We use the ColorPanel component from the block editor to render the color panel in the style settings sidebar.
 */
const { ColorPanel: StylesColorPanel } = unlock( blockEditorPrivateApis );

/**
 * The useGlobalStylesOutputWithConfig is used to generate the CSS for the email editor content from the style settings.
 */
const { useGlobalStylesOutputWithConfig } = unlock( blockEditorPrivateApis );

/**
 * The Editor is the main component for the email editor.
 */
const { Editor, FullscreenMode, ViewMoreMenuGroup, BackButton } =
	unlock( editorPrivateApis );

export {
	StylesColorPanel,
	useGlobalStylesOutputWithConfig,
	Editor,
	FullscreenMode,
	ViewMoreMenuGroup,
	BackButton,
};
