/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

export interface EditorColors {
	editorBackgroundColor: string;
	editorColor: string;
}

/**
 * Hook to inject a <style> element in the Site Editor using theme background and foreground colors.
 *
 * @param styleId         - ID of the style tag to create and append.
 * @param getStyleContent - Callback that receives editor colors and returns CSS to inject.
 */
export const useApplyEditorStyles = (
	styleId: string,
	getStyleContent: ( colors: EditorColors ) => string
): void => {
	useEffect( () => {
		// Find the editor styles wrapper in the main document.
		let editorStylesWrapper = document.querySelector(
			'.editor-styles-wrapper'
		);

		// If not found in main document, try to find it in the site editor iframe.
		if ( ! editorStylesWrapper ) {
			const canvasEl = document.querySelector(
				'.edit-site-visual-editor__editor-canvas'
			) as HTMLIFrameElement | null;

			if ( ! canvasEl || ! ( canvasEl instanceof HTMLIFrameElement ) ) {
				return;
			}

			const canvasDoc =
				canvasEl.contentDocument || canvasEl.contentWindow?.document;
			if ( ! canvasDoc ) {
				return;
			}

			// Look for the editor styles wrapper inside the iframe.
			editorStylesWrapper = canvasDoc.querySelector(
				'.editor-styles-wrapper'
			);
		}

		if ( ! editorStylesWrapper ) {
			return;
		}

		// Get the computed background and text color of the editor.
		const computedStyles = window.getComputedStyle( editorStylesWrapper );
		const editorBackgroundColor = computedStyles?.backgroundColor;
		const editorColor = computedStyles?.color;

		const alreadyInjected = editorStylesWrapper.querySelector(
			`#${ styleId }`
		);

		// Inject a new <style> tag only if not already added and colors are available.
		if ( ! alreadyInjected && editorBackgroundColor && editorColor ) {
			const styleElement = document.createElement( 'style' );
			styleElement.id = styleId;
			styleElement.appendChild(
				document.createTextNode(
					getStyleContent( {
						editorBackgroundColor,
						editorColor,
					} )
				)
			);
			editorStylesWrapper.appendChild( styleElement );
		}
	}, [ styleId, getStyleContent ] );
};
