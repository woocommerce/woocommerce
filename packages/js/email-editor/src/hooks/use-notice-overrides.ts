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
			content: __( 'Email design updated.', __i18n_text_domain__ ),
			removeActions: true,
		},
		'editor-save': {
			content: __( 'Email saved.', __i18n_text_domain__ ),
			// Gutenberg attaches an action linking to the post permalink,
			// labelled with the post type's `view_item` label, which reads
			// as "View Post"/"View Email" for an email. Drop it: a preview
			// is already available from the editor header.
			removeActions: true,
			// "Draft saved." is intentionally NOT rewritten: a saved draft is
			// not used for sending, and "Email saved." would suggest it is.
			contentCheck: ( content: string ) =>
				// Intentionally without text domain to match the core translations.
				content.includes( __( 'Post updated.' ) ) ||
				content.includes( __( 'Post published.' ) ),
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

	const actions = override.removeActions ? [] : notice.actions;

	if ( override.contentCheck && ! override.contentCheck( notice.content ) ) {
		return { ...notice, actions };
	}

	return {
		...notice,
		content: override.content,
		spokenMessage: override.content,
		actions,
	};
}

function applyOverridesToNotices( notices: Notice[] ): Notice[] {
	return notices.map( ( notice ) => transformNotice( notice ) );
}

function getStoreName( namespace: string | { name: string } ): string {
	return typeof namespace === 'object' ? namespace.name : namespace;
}

const getNoticesWithOverrides = createSelector(
	( notices: Notice[] ) => applyOverridesToNotices( notices ),
	( notices: Notice[] ) => [ notices ]
);

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
