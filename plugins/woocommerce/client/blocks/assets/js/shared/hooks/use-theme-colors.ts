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
 * @param styleId         - The ID of the style element to inject.
 * @param getStyleContent - Callback that receives editor colors and returns CSS to inject.
 */
export const useThemeColors = (
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

		if ( ! editorBackgroundColor || ! editorColor ) {
			return;
		}

		const styleElementId = `${ styleId }-editor-theme-colors`;

		const alreadyInjected = editorStylesWrapper.querySelector(
			`#${ styleElementId }`
		);

		// Check if we've already injected a style with this id.
		if ( alreadyInjected ) {
			return;
		}

		// Generate the content for this style.
		const styleContent = getStyleContent( {
			editorBackgroundColor,
			editorColor,
		} );

		// Create and inject the style element with the specific ID.
		const styleElement = document.createElement( 'style' );
		styleElement.id = styleElementId;
		styleElement.appendChild( document.createTextNode( styleContent ) );
		editorStylesWrapper.appendChild( styleElement );

		/**
		 * We are intentionally not cleaning up the style element here, because
		 * blocks might be remounted many times, so this way we avoid removing
		 * and appending the style element many times.
		 */
	}, [ getStyleContent, styleId ] );
};
