/**
 * External dependencies
 */
import { memo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ScreenTypography,
	ScreenTypographyElement,
	ScreenLayout,
	ScreenRoot,
	ScreenColors,
	ScreenBackground,
} from './screens';
import { Navigator } from './navigator';

/**
 * The global email styles UI, without any surrounding editor chrome.
 *
 * `StylesSidebar` renders this inside a `PluginSidebar`, which is what the
 * email editor itself wants. Consumers that already own their container —
 * their own sidebar, a modal, a settings screen — render this directly
 * instead, and gate it themselves with `useCanEditEmailStyles`.
 *
 * The panel reads and writes through the `email-editor/editor` store, so the
 * store must be registered with `createStore()` and configured with
 * `setEditorConfig()` before this renders.
 */
export function RawStylesPanel(): JSX.Element {
	return (
		<div className="woocommerce-email-editor-styles-panel">
			<Navigator initialPath="/">
				<Navigator.Screen path="/">
					<ScreenRoot />
				</Navigator.Screen>

				<Navigator.Screen path="/typography">
					<ScreenTypography />
				</Navigator.Screen>

				<Navigator.Screen path="/typography/text">
					<ScreenTypographyElement element="text" />
				</Navigator.Screen>

				<Navigator.Screen path="/typography/link">
					<ScreenTypographyElement element="link" />
				</Navigator.Screen>

				<Navigator.Screen path="/typography/heading">
					<ScreenTypographyElement element="heading" />
				</Navigator.Screen>

				<Navigator.Screen path="/typography/button">
					<ScreenTypographyElement element="button" />
				</Navigator.Screen>

				<Navigator.Screen path="/colors">
					<ScreenColors />
				</Navigator.Screen>

				<Navigator.Screen path="/background">
					<ScreenBackground />
				</Navigator.Screen>

				<Navigator.Screen path="/layout">
					<ScreenLayout />
				</Navigator.Screen>
			</Navigator>
		</div>
	);
}

export const StylesPanel = memo( RawStylesPanel );
