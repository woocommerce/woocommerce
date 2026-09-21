/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { createSelector, use } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { storeName } from '../store/constants';

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
	labelKeys?: string[];
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
			// The notice text is rewritten only when it equals one of the
			// post type's success labels, which WordPress translates on the
			// server (so this works in any site locale). "Draft saved." is
			// deliberately left as-is: a saved draft is not used for
			// sending, and "Email saved." would suggest it is.
			labelKeys: [
				'item_updated',
				'item_published',
				'item_published_privately',
				'item_scheduled',
			],
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

type PostTypeLabels = Record< string, string > | undefined;

// Post types whose own save notices ("Template updated.", …) must not be
// rewritten to "Email saved." — that text is reserved for actual email
// post types. The action is still dropped for these, same as any other
// `editor-save` notice.
const TEMPLATE_POST_TYPES = [ 'wp_template', 'wp_template_part' ];

function isTemplatePostType( postType: string | undefined ): boolean {
	return !! postType && TEMPLATE_POST_TYPES.includes( postType );
}

function transformNotice( notice: Notice, labels: PostTypeLabels ): Notice {
	const overrides = getNoticeOverrides();
	const override = overrides[ notice.id ];
	if ( ! override ) {
		return notice;
	}

	const rewriteText =
		! override.labelKeys ||
		override.labelKeys.some(
			( key ) => labels?.[ key ] && labels[ key ] === notice.content
		);

	return {
		...notice,
		...( rewriteText
			? { content: override.content, spokenMessage: override.content }
			: {} ),
		actions: override.removeActions ? [] : notice.actions,
	};
}

function applyOverridesToNotices(
	notices: Notice[],
	labels: PostTypeLabels
): Notice[] {
	return notices.map( ( notice ) => transformNotice( notice, labels ) );
}

function getStoreName( namespace: string | { name: string } ): string {
	return typeof namespace === 'object' ? namespace.name : namespace;
}

const getNoticesWithOverrides = createSelector(
	( notices: Notice[], labels: PostTypeLabels ) =>
		applyOverridesToNotices( notices, labels ),
	( notices: Notice[], labels: PostTypeLabels ) => [ notices, labels ]
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
						getNotices: ( context?: string ) => {
							const notices = originalGetNotices( context );
							const overrides = getNoticeOverrides();
							const hasOverridableNotice = notices.some(
								( notice ) => overrides[ notice.id ]
							);

							if ( ! hasOverridableNotice ) {
								return getNoticesWithOverrides(
									notices,
									undefined
								);
							}

							const postType = (
								originalSelect( storeName ) as
									| { getEmailPostType?: () => string }
									| undefined
							 )?.getEmailPostType?.();
							const labels =
								postType && ! isTemplatePostType( postType )
									? (
											originalSelect( coreStore ) as
												| {
														getPostType: (
															postType: string
														) => {
															labels?: PostTypeLabels;
														};
												  }
												| undefined
									   )?.getPostType( postType )?.labels
									: undefined;

							return getNoticesWithOverrides( notices, labels );
						},
					};
				},
			};
		} );

		return () => {
			use( () => ( { select: originalSelect } ) );
		};
	}, [] );
}
