/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableIframe as Iframe,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableUseMouseMoveTypingReset as useMouseMoveTypingReset,
} from '@wordpress/block-editor';

type EditorCanvasProps = {
	enableResizing: boolean;
	children: React.ReactNode;
};

export function EditorCanvas( {
	enableResizing,
	children,
	...props
}: EditorCanvasProps ) {
	const mouseMoveTypingRef = useMouseMoveTypingReset();
	return (
		<Iframe
			head={
				<>
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
				</>
			}
			ref={ mouseMoveTypingRef }
			name="editor-canvas"
			className="edit-site-visual-editor__editor-canvas"
			{ ...props }
		>
			{ children }
		</Iframe>
	);
}
