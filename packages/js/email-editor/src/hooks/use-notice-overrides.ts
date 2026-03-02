/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { createSelector, use } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Wraps the `getNotices` selector on the notices store so that specific
 * notices are returned with email-editor-appropriate content.
 *
 * Use the `useNoticeOverrides` hook when mounting the email editor. On
 * mount it applies a data plugin that overrides the selector; on unmount
 * it applies a plugin that restores the original select.
 */

interface NoticeOverride {
	content: string;
	removeActions: boolean;
	contentCheck?: ( content: string ) => boolean;
}

function getNoticeOverrides(): Record< string, NoticeOverride > {
	return {
		'site-editor-save-success': {
			content: __( 'Email design updated.', 'woocommerce' ),
			removeActions: true,
		},
		'editor-save': {
			content: __( 'Email saved.', 'woocommerce' ),
			removeActions: false,
			contentCheck: ( content: string ) =>
				// Intentionally without text domain to match the core translation.
				// eslint-disable-next-line @wordpress/i18n-text-domain
				content.includes( __( 'Post updated.' ) ),
		},
	};
}

interface Notice {
	id: string;
	content: string;
	spokenMessage: string;
	actions: unknown[];
	[ key: string ]: unknown;
}

function transformNotice( notice: Notice ): Notice {
	const overrides = getNoticeOverrides();
	const override = overrides[ notice.id ];
	if ( ! override ) {
		return notice;
	}
	if ( override.contentCheck && ! override.contentCheck( notice.content ) ) {
		return notice;
	}
	return {
		...notice,
		content: override.content,
		spokenMessage: override.content,
		actions: override.removeActions ? [] : notice.actions,
	};
}

function applyOverridesToNotices( notices: Notice[] ): Notice[] {
	return notices.map( ( notice ) => transformNotice( notice ) );
}

function getStoreName( namespace: string | { name: string } ): string {
	return typeof namespace === 'object' ? namespace.name : namespace;
}

/**
 * Applies notice overrides when the email editor is mounted and restores
 * the original select when it unmounts.
 */
export function useNoticeOverrides(): void {
	useEffect( () => {
		let originalSelect: ( namespace: string | { name: string } ) => unknown;

		use( ( registry: { select: ( ...args: unknown[] ) => unknown } ) => {
			originalSelect = registry.select;

			return {
				select: ( namespace: string | { name: string } ) => {
					if ( getStoreName( namespace ) !== noticesStore.name ) {
						return originalSelect( namespace );
					}

					const selectors = originalSelect( namespace ) as {
						getNotices?: ( context?: string ) => Notice[];
						[ key: string ]: unknown;
					};

					const originalGetNotices = selectors.getNotices;
					if ( ! originalGetNotices ) {
						return selectors;
					}

					const getNoticesWithOverrides = createSelector(
						( notices: Notice[] ) =>
							applyOverridesToNotices( notices ),
						( notices: Notice[] ) => [ notices ]
					);

					return {
						...selectors,
						getNotices: ( context?: string ) =>
							getNoticesWithOverrides(
								originalGetNotices( context )
							),
					};
				},
			};
		} );

		return () => {
			use( () => ( { select: originalSelect } ) );
		};
	}, [] );
}
