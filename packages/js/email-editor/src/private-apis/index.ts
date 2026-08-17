/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';
import {
	privateApis as editorPrivateApis,
	store as editorStore,
} from '@wordpress/editor';
import {
	// @ts-expect-error privateApis is not in the DT types for @wordpress/block-editor.
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';

const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
	'@wordpress/edit-site' // The module name must be in the list of allowed, so for now I used the package name of the post editor
);

/**
 * We use the ColorPanel and BackgroundPanel components from the block editor to render
 * the color and background panels in the style settings sidebar.
 *
 * Since WordPress 7.1 the ColorPanel no longer renders the text and background color
 * controls — text color lives in the typography panel and background color in the
 * background panel. The useHasColorPanel and useHasBackgroundPanel hooks let us detect
 * where the controls live in the running WordPress version. The fallbacks cover a WordPress
 * version whose private API surface lacks these exports and resolve to the legacy
 * behavior: text and background color handled by ColorPanel, no background screen.
 */
const {
	ColorPanel: StylesColorPanel,
	BackgroundPanel,
	useHasColorPanel,
	useHasBackgroundPanel,
} = unlock( blockEditorPrivateApis );

const StylesBackgroundPanel = BackgroundPanel ?? ( () => null );
const useHasStylesColorPanel = useHasColorPanel ?? ( () => true );
const useHasStylesBackgroundPanel = useHasBackgroundPanel ?? ( () => false );

/**
 * The Editor is the main component for the email editor.
 */
const { Editor, FullscreenMode, ViewMoreMenuGroup, BackButton } =
	unlock( editorPrivateApis );

/**
 * The registerEntityAction and unregisterEntityAction are used to register and unregister entity actions.
 * This is used in the move-to-trash.tsx file to modify the move to trash action.
 * Providing us with the ability to remove the default move to trash action and add a custom trash email post action.
 */
const { registerEntityAction, unregisterEntityAction } = unlock(
	dispatch( editorStore )
);

export {
	StylesColorPanel,
	StylesBackgroundPanel,
	useHasStylesColorPanel,
	useHasStylesBackgroundPanel,
	Editor,
	FullscreenMode,
	ViewMoreMenuGroup,
	BackButton,
	registerEntityAction,
	unregisterEntityAction,
};
