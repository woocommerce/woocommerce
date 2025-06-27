/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordEvent, isEventTrackingEnabled } from '.';

let EVENTS_TO_TRACK = [];

/**
 * Filter events by selector and record the event.
 */
function trackMatchingEvents( event: Event ) {
	EVENTS_TO_TRACK.forEach( ( candidate ) => {
		const matchedTarget = ( event.target as Element )?.matches?.(
			candidate.selector
		)
			? event.target
			: ( event.target as Element )?.closest?.( candidate.selector );

		// Event doesn't match any of our watched selectors so we skip it
		if ( ! matchedTarget ) {
			return;
		}

		if ( typeof candidate.track === 'function' ) {
			candidate.track( matchedTarget, event );
		} else {
			recordEvent( candidate.track );
		}
	} );
}

export function initDomTracking() {
	if ( ! isEventTrackingEnabled() ) {
		return;
	}

	/**
	 * Events to track.
	 * Properties:
	 * - track: The event to track of callback to handle tracking.
	 * - selector: The selector to match the event.
	 */
	EVENTS_TO_TRACK = [
		// Header preview dropdown preview in new tab selected
		{
			track: 'header_preview_dropdown_preview_in_new_tab_selected',
			selector: '.editor-preview-dropdown__button-external',
		},
		// Header toggle block tools
		{
			track: () => {
				const isBlockToolsCollapsed = document.getElementsByClassName(
					'is-collapsed editor-collapsible-block-toolbar'
				).length;
				recordEvent( 'header_blocks_tool_button_clicked', {
					isBlockToolsCollapsed,
				} );
			},
			selector: '.editor-collapsible-block-toolbar__toggle',
		},
		// Header more menu toggle
		{
			track: ( target ) => {
				const isOpened = target.classList.contains( 'is-opened' );
				recordEvent( 'header_more_menu_dropdown_toggle', {
					isOpened,
				} );
			},
			// eslint-disable-next-line @wordpress/i18n-text-domain
			selector: `.components-dropdown-menu__toggle[aria-label="${ __(
				'Options'
			) }"]`,
		},
		// Header save button clicked
		{
			track: ( target ) => {
				if (
					// eslint-disable-next-line @wordpress/i18n-text-domain
					( target.textContent === __( 'Save' ) &&
						target.getAttribute( 'aria-disabled' ) === 'false' ) ||
					// eslint-disable-next-line @wordpress/i18n-text-domain
					target.textContent === __( 'Saving…' )
				) {
					recordEvent( 'header_save_button_clicked' );
				}
			},
			selector: '.editor-post-publish-button',
		},
		// Header save draft button clicked
		{
			track: 'header_save_email_button_clicked',
			selector: '.editor-post-saved-state.is-saving',
		},
		// Inserter panel close icon clicked
		{
			track: 'inserter_sidebar_library_close_icon_clicked',
			selector:
				'.block-editor-inserter__menu .block-editor-tabbed-sidebar__close-button',
		},
		// Preview dropdown toggle clicked
		{
			track: ( target ) => {
				const isOpened = target.classList.contains( 'is-opened' );
				recordEvent( 'header_preview_dropdown_clicked', {
					isOpened,
				} );
			},
			selector: '.editor-preview-dropdown__toggle',
		},
		// Email tab in the sidebar clicked
		{
			track: () => {
				recordEvent( 'sidebar_tab_selected', { tab: 'document' } );
			},
			selector: '[data-tab-id="edit-post/document"]',
		},
		// Block tab in the sidebar clicked
		{
			track: () => {
				recordEvent( 'sidebar_tab_selected', { tab: 'block' } );
			},
			selector: '[data-tab-id="edit-post/block"]',
		},
		// Header inserter sidebar toggle clicked
		{
			track: ( target ) => {
				const isOpened = target.classList.contains( 'is-pressed' );
				recordEvent( 'header_inserter_sidebar_clicked', { isOpened } );
			},
			selector: '.editor-document-tools__inserter-toggle',
		},
		// Header listview sidebar toggle clicked
		{
			track: ( target ) => {
				const isOpened = target.classList.contains( 'is-pressed' );
				recordEvent( 'header_listview_sidebar_clicked', { isOpened } );
			},
			selector: '.editor-document-tools__document-overview-toggle',
		},
		// Command in command bar selected
		{
			track: ( target ) => {
				recordEvent( 'command_bar_command_clicked', {
					command: target.dataset?.value,
				} );
			},
			selector: '.commands-command-menu__container [role="option"]',
		},
	];

	document.addEventListener( 'click', trackMatchingEvents );
}
