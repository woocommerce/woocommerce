/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordEvent, isEventTrackingEnabled } from './event-pipeline';

let EVENTS_TO_TRACK = [];

const SIDEBAR_TABS = {
	'edit-post/document': 'document',
	'edit-post/block': 'block',
};

/**
 * Records whether a header menu is open after the click on its toggle.
 * WordPress 7.2 menus set only `aria-expanded` (no `is-opened` class), and only after the
 * click event, so the state is read once the click has been handled.
 */
function recordMenuToggle( eventName: string, target: Element ) {
	setTimeout( () => {
		recordEvent( eventName, {
			isOpened:
				target.classList.contains( 'is-opened' ) ||
				target.getAttribute( 'aria-expanded' ) === 'true',
		} );
	} );
}

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
			// WP 7.1 dropped the `.editor-preview-dropdown__button-external`
			// class; the entry is now a menu item anchor whose window target is
			// `wp-preview-<postId>`. Match both so the event keeps firing across
			// supported WordPress versions.
			selector:
				'.editor-preview-dropdown__button-external, a[role="menuitem"][target^="wp-preview-"]',
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
			track: ( target ) =>
				recordMenuToggle( 'header_more_menu_dropdown_toggle', target ),
			selector: `.editor-header__settings button[aria-label="${ __(
				'Options'
			) }"]`,
		},
		// Header save button clicked
		{
			track: ( target ) => {
				if (
					( target.textContent === __( 'Save' ) &&
						target.getAttribute( 'aria-disabled' ) === 'false' ) ||
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
			track: ( target ) =>
				recordMenuToggle( 'header_preview_dropdown_clicked', target ),
			selector: '.editor-preview-dropdown__toggle',
		},
		// Email or Block tab in the sidebar clicked
		{
			// The editor has already switched the sidebar when the click reaches the document.
			track: () => {
				const tab =
					SIDEBAR_TABS[
						select( 'core/interface' ).getActiveComplementaryArea(
							'core'
						)
					];
				if ( tab ) {
					recordEvent( 'sidebar_tab_selected', { tab } );
				}
			},
			// WordPress 7.0 renders the tabs with role="button", but with `data-tab-id`.
			selector:
				'.editor-sidebar__panel-tabs [data-tab-id], .editor-sidebar__panel-tabs [role="tab"]',
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
