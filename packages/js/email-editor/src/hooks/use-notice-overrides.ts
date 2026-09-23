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

// Shared with the `editor-save` override below so a template design save
// and an in-editor design save report the exact same wording.
const EMAIL_DESIGN_UPDATED_MESSAGE = __(
	'Email design updated.',
	__i18n_text_domain__
);

function getNoticeOverrides(): Record< string, NoticeOverride > {
	return {
		'site-editor-save-success': {
			content: EMAIL_DESIGN_UPDATED_MESSAGE,
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
			// `item_published_privately` and `item_scheduled` are omitted:
			// the editor removes the "post-status" panel on mount (see
			// `block-editor/editor.tsx`), so there is no UI to set an email
			// post's visibility to private or its status to `future` — those
			// labels can never match a real notice here.
			labelKeys: [ 'item_updated', 'item_published' ],
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

// Post types whose own save notices ("Template updated.", …) get their own
// "Email design updated." wording instead of "Email saved." — that text is
// reserved for actual email post types, since saving a template is a design
// change, not a change to the email content itself.
const TEMPLATE_POST_TYPES = [ 'wp_template', 'wp_template_part' ];

function isTemplatePostType( postType: string | undefined ): boolean {
	return !! postType && TEMPLATE_POST_TYPES.includes( postType );
}

function transformNotice(
	notice: Notice,
	labels: PostTypeLabels,
	postType: string | undefined
): Notice {
	const overrides = getNoticeOverrides();
	// A plain lookup would resolve ids like `constructor` or `toString` to
	// an inherited `Object.prototype` member instead of `undefined`.
	if ( ! Object.prototype.hasOwnProperty.call( overrides, notice.id ) ) {
		return notice;
	}
	const override = overrides[ notice.id ];

	const rewriteText =
		! override.labelKeys ||
		override.labelKeys.some(
			( key ) => labels?.[ key ] && labels[ key ] === notice.content
		);

	const content =
		notice.id === 'editor-save' && isTemplatePostType( postType )
			? EMAIL_DESIGN_UPDATED_MESSAGE
			: override.content;

	return {
		...notice,
		...( rewriteText ? { content, spokenMessage: content } : {} ),
		actions: override.removeActions ? [] : notice.actions,
	};
}

function applyOverridesToNotices(
	notices: Notice[],
	labels: PostTypeLabels,
	postType: string | undefined
): Notice[] {
	return notices.map( ( notice ) =>
		transformNotice( notice, labels, postType )
	);
}

function getStoreName( namespace: string | { name: string } ): string {
	return typeof namespace === 'object' ? namespace.name : namespace;
}

const getNoticesWithOverrides = createSelector(
	(
		notices: Notice[],
		labels: PostTypeLabels,
		postType: string | undefined
	) => applyOverridesToNotices( notices, labels, postType ),
	(
		notices: Notice[],
		labels: PostTypeLabels,
		postType: string | undefined
	) => [ notices, labels, postType ]
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
								( notice ) =>
									Object.prototype.hasOwnProperty.call(
										overrides,
										notice.id
									)
							);

							if ( ! hasOverridableNotice ) {
								return getNoticesWithOverrides(
									notices,
									undefined,
									undefined
								);
							}

							const postType = (
								originalSelect( storeName ) as
									| { getEmailPostType?: () => string }
									| undefined
							 )?.getEmailPostType?.();
							const labels = postType
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

							return getNoticesWithOverrides(
								notices,
								labels,
								postType
							);
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
