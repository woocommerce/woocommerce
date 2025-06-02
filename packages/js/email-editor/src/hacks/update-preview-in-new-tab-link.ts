/**
 * Internal dependencies
 */
import { recordEvent } from '../events';

/**
 * This function is used to handle the click event of the open preview in new tab button.
 * The Click events are being intercepted by the core editor, so we need to handle them here.
 *
 * @param link - The link to the preview.
 */
const handleOpenPreviewInNewTabClick = ( link: string ) => {
	const targetId = `wp-preview-${ window.WooCommerceEmailEditor.current_post_id }`;

	// Open up a new window with the target id and reuse it if it already exists.
	const previewWindow = window.open( '', targetId );

	previewWindow?.focus();

	previewWindow.location = link;
};

/**
 * This function is used to replace the preview in new tab link with a link that doesn't include the preview_nonce parameter.
 * This is used to prevent the preview from being cached and to ensure that the preview is always up to date.
 */
function replacePreviewInNewTabLink() {
	const previewInNewTabLinkSelector =
		'.editor-preview-dropdown__button-external';

	const previewInNewTabLinkElem = document.querySelector(
		previewInNewTabLinkSelector
	) as HTMLAnchorElement;

	if ( ! previewInNewTabLinkElem ) {
		return;
	}

	if ( ! previewInNewTabLinkElem.href.includes( 'preview_nonce' ) ) {
		return;
	}

	// remove the preview_nonce and value from the href
	const urlHandler = new URL( previewInNewTabLinkElem.href );
	urlHandler.searchParams.delete( 'preview_nonce' );
	const newHref = urlHandler.toString();
	previewInNewTabLinkElem.href = newHref;

	previewInNewTabLinkElem.addEventListener( 'click', ( event: Event ) => {
		// manually tracking here as stopPropagation will break the other dom event listener.
		recordEvent( 'header_preview_dropdown_preview_in_new_tab_selected' );

		event.preventDefault();

		handleOpenPreviewInNewTabClick( newHref );
		// Stop the event from bubbling up to the core editor or other event listeners.
		event.stopPropagation();
	} );
}

/**
 * This function is used to update the preview in new tab link.
 * It adds an event listener to the preview button to replace the preview in new tab link when the preview button is clicked.
 * Hacking it this way is not ideal, but it's the only way to ensure that the preview in new tab link is always up to date.
 */
function updatePreviewInNewTabLink() {
	const previewSelector = '.editor-preview-dropdown__toggle';

	document.addEventListener( 'click', ( event: Event ) => {
		const matchedTarget = ( event.target as Element )?.matches?.(
			previewSelector
		)
			? event.target
			: ( event.target as Element )?.closest?.( previewSelector );

		// Event doesn't match any of our watched selectors so we skip it
		if ( ! matchedTarget ) {
			return;
		}

		replacePreviewInNewTabLink();
	} );
}

export { updatePreviewInNewTabLink };
