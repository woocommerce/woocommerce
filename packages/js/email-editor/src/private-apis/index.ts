/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';
import {
	// @ts-expect-error No types for privateApis.
	privateApis as editorPrivateApis,
	store as editorStore,
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
	Editor,
	FullscreenMode,
	ViewMoreMenuGroup,
	BackButton,
	registerEntityAction,
	unregisterEntityAction,
};
