/**
 * External dependencies
 */
import { createElement, createRoot } from '@wordpress/element';
import type { ComponentType, ReactNode } from 'react';
import type { SettingsUISchema } from '@woocommerce/settings-ui';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

declare global {
	interface Window {
		wc?: {
			settingsUi?: {
				SettingsUIErrorBoundary: ComponentType< {
					children: ReactNode;
					onError?: () => void;
				} >;
				SettingsUIPage: ( props: {
					schema: SettingsUISchema;
					page: string;
					section?: string;
				} ) => JSX.Element | null;
			};
		};
	}
}

// The legacy header is hidden immediately (see settings-ui.scss) on the
// assumption the React shell will mount successfully, so every detectable
// failure must add this class back to restore it instead of leaving the
// page without any header.
const RENDER_FAILED_CLASS = 'woocommerce-settings-ui-render-failed';
const DRILL_DOWN_CLASS = 'woocommerce-settings-ui-drill-down';

// Catch-all for failures nothing else in this module can observe directly,
// e.g. the whole settings-ui bundle silently failing to execute (blocked by
// a browser extension, CSP, a corrupted cached asset). Long enough to cover
// a slow connection or cold cache without false-positiving on a normal load.
const RENDER_WATCHDOG_MS = 4000;

export const markSettingsUIRenderFailed = () => {
	document.body.classList.add( RENDER_FAILED_CLASS );
};

const isShellHeaderPresent = () =>
	!! document.querySelector( '.wc-settings-ui-shell__header' );

// The watchdog can only observe a snapshot at RENDER_WATCHDOG_MS, but React 18
// concurrent rendering may still be mounting past that point on a slow
// connection or cold cache. A MutationObserver keeps watching afterwards so a
// late-but-successful mount clears the failure class instead of leaving it
// (and the restored legacy header) stuck alongside the React header.
const scheduleSettingsUIRenderWatchdog = () => {
	if ( ! document.body.classList.contains( DRILL_DOWN_CLASS ) ) {
		return;
	}

	const observer = new MutationObserver( () => {
		if ( isShellHeaderPresent() ) {
			observer.disconnect();
			document.body.classList.remove( RENDER_FAILED_CLASS );
		}
	} );
	observer.observe( document.body, { childList: true, subtree: true } );

	setTimeout( () => {
		if ( ! isShellHeaderPresent() ) {
			// eslint-disable-next-line no-console
			console.warn(
				'[WooCommerce settings UI] The settings page did not render within the expected time.'
			);
			markSettingsUIRenderFailed();
		}
	}, RENDER_WATCHDOG_MS );
};

const getSchema = (
	page: string,
	section: string
): SettingsUISchema | undefined => {
	const settings = getAdminSetting( 'settingsUI', {} );
	const sectionKey = section || 'default';
	return settings?.[ page ]?.[ sectionKey ];
};

export const registerSettingsUIScreens = () => {
	scheduleSettingsUIRenderWatchdog();

	const SettingsUIErrorBoundary =
		window.wc?.settingsUi?.SettingsUIErrorBoundary;
	const SettingsUIPage = window.wc?.settingsUi?.SettingsUIPage;

	if ( ! SettingsUIErrorBoundary || ! SettingsUIPage ) {
		if (
			document.querySelector< HTMLElement >( '[data-wc-settings-ui="1"]' )
		) {
			// eslint-disable-next-line no-console
			console.warn(
				'[WooCommerce settings UI] The wc-settings-ui script is missing.'
			);
			markSettingsUIRenderFailed();
		}
		return;
	}

	document
		.querySelectorAll< HTMLElement >( '[data-wc-settings-ui="1"]' )
		.forEach( ( element ) => {
			const page = element.dataset.wcSettingsPage || '';
			const section = element.dataset.wcSettingsSection || '';
			const schema = getSchema( page, section );

			if ( ! schema ) {
				// eslint-disable-next-line no-console
				console.warn(
					'[WooCommerce settings UI] Settings payload is missing.',
					{ page, section }
				);
				markSettingsUIRenderFailed();
				return;
			}

			try {
				createRoot( element ).render(
					createElement( SettingsUIErrorBoundary, {
						onError: markSettingsUIRenderFailed,
						children: createElement( SettingsUIPage, {
							schema,
							page,
							section,
						} ),
					} )
				);
			} catch ( mountError: unknown ) {
				// eslint-disable-next-line no-console
				console.warn(
					'[WooCommerce settings UI] Mounting the settings page failed.',
					mountError
				);
				markSettingsUIRenderFailed();
			}
		} );
};
