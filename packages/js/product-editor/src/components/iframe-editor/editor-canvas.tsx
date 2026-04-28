/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import {
	EditorSettings,
	EditorBlockListSettings,
	// @ts-expect-error __unstableIframe is not exported from @wordpress/block-editor's public types.
	__unstableIframe as Iframe,
	// @ts-expect-error __unstableUseMouseMoveTypingReset is not exported from @wordpress/block-editor's public types.
	__unstableUseMouseMoveTypingReset as useMouseMoveTypingReset,
	// @ts-expect-error __unstableEditorStyles is not exported from @wordpress/block-editor's public types.
	__unstableEditorStyles as EditorStyles,
} from '@wordpress/block-editor';

type EditorCanvasProps = {
	children: React.ReactNode;
	enableResizing: boolean;
	settings: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function EditorCanvas( {
	children,
	enableResizing,
	settings,
	...props
}: EditorCanvasProps ) {
	const mouseMoveTypingRef = useMouseMoveTypingReset();
	return (
		<Iframe
			ref={ mouseMoveTypingRef }
			name="editor-canvas"
			className="edit-site-visual-editor__editor-canvas"
			{ ...props }
		>
			<>
				<EditorStyles styles={ settings?.styles } />
				<style>
					{
						// Forming a "block formatting context" to prevent margin collapsing.
						// @see https://developer.mozilla.org/en-US/docs/Web/Guide/CSS/Block_formatting_context
						`.is-root-container {
								padding: 36px;
								display: flow-root;
							}
							body { position: relative; }`
					}
				</style>
				{ enableResizing && (
					<style>
						{
							// Some themes will have `min-height: 100vh` for the root container,
							// which isn't a requirement in auto resize mode.
							`.is-root-container { min-height: 0 !important; }`
						}
					</style>
				) }
				{ children }
			</>
		</Iframe>
	);
}
