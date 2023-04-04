/**
 * External dependencies
 */
import {
	__experimentalUseResizeCanvas as useResizeCanvas,
	__unstableEditorStyles as EditorStyles,
	__unstableIframe as Iframe,
	__unstableUseMouseMoveTypingReset as useMouseMoveTypingReset,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createElement, Fragment } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

export function EditorCanvas( {
	enableResizing,
	settings,
	children,
	...props
} ) {
	const { deviceType, isZoomOutMode } = useSelect(
		( select ) => ( {
			isZoomOutMode:
				select( blockEditorStore ).__unstableGetEditorMode() ===
				'zoom-out',
		} ),
		[]
	);
	const deviceStyles = useResizeCanvas( deviceType );
	const mouseMoveTypingRef = useMouseMoveTypingReset();
	return (
		<Iframe
			expand={ isZoomOutMode }
			scale={ ( isZoomOutMode && 0.45 ) || undefined }
			frameSize={ isZoomOutMode ? 100 : undefined }
			style={ enableResizing ? {} : deviceStyles }
			head={
				<>
					<EditorStyles styles={ settings.styles } />
					<style>
						{
							// Forming a "block formatting context" to prevent margin collapsing.
							// @see https://developer.mozilla.org/en-US/docs/Web/Guide/CSS/Block_formatting_context
							`.is-root-container { display: flow-root; }
							body { position: relative; }
                            .block-editor-block-list__layout {
                                padding: 36px;
                            }`
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
