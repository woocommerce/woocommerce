/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

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
	const styleElementRef = useRef< HTMLStyleElement | null >( null );

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

		// Check if we already have a style element.
		let styleElement = editorStylesWrapper.querySelector(
			`#${ styleElementId }`
		) as HTMLStyleElement | null;

		// If no style element exists, create one.
		if ( ! styleElement ) {
			styleElement = document.createElement( 'style' );
			styleElement.id = styleElementId;
			editorStylesWrapper.appendChild( styleElement );
		}

		// Store reference to the style element.
		styleElementRef.current = styleElement;

		// Generate and update the style content.
		const styleContent = getStyleContent( {
			editorBackgroundColor,
			editorColor,
		} );

		// Update the style content.
		styleElement.textContent = styleContent;

		// Set up a MutationObserver to watch for style changes.
		const observer = new MutationObserver( () => {
			const newComputedStyles =
				window.getComputedStyle( editorStylesWrapper );
			const newBackgroundColor = newComputedStyles?.backgroundColor;
			const newColor = newComputedStyles?.color;

			if (
				newBackgroundColor !== editorBackgroundColor ||
				newColor !== editorColor
			) {
				// Update the style content with new colors.
				const newStyleContent = getStyleContent( {
					editorBackgroundColor: newBackgroundColor,
					editorColor: newColor,
				} );
				if ( styleElementRef.current ) {
					styleElementRef.current.textContent = newStyleContent;
				}
			}
		} );

		// Start observing the editor styles wrapper for style changes.
		observer.observe( editorStylesWrapper, {
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		return () => {
			observer.disconnect();
			if (
				styleElementRef.current &&
				styleElementRef.current.parentNode
			) {
				styleElementRef.current.parentNode.removeChild(
					styleElementRef.current
				);
			}
		};
	}, [ getStyleContent, styleId ] );
};
