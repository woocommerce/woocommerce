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
import { storeName as emailEditorStoreName } from '../store/constants';

/**
 * Store name of the WordPress editor store.
 * Importing `@wordpress/editor` pulls in `@wordpress/block-editor`'s
 * `transform-styles` util, which depends on the ESM-only `parsel-js`
 * package — Jest can't transform it, so unit tests fail to load. The
 * hardcoded string avoids that dependency chain.
 */
const CORE_EDITOR_STORE = 'core/editor';

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
			// `item_trashed` and `item_reverted_to_draft` are omitted too:
			// Gutenberg only puts them on an `editor-save` notice by way of
			// its own `trashPost()` and status-change flows, both of which
			// end in a `dispatch.savePost()` call. The editor doesn't use
			// either — trashing an email goes through a custom action that
			// calls `deleteEntityRecord()` directly and reports its own
			// `trash-email-post-action` notice (see
			// `components/header/trash-email-post.tsx`), and there is no UI
			// to revert a published post to draft once the "post-status"
			// panel is removed above. So these labels can never match a real
			// `editor-save` notice either.
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

// A notice's wording is decided by which post type's labels its content
// matches, not by whichever post type happens to be current when
// `getNotices()` runs — see the module docblock above `getNoticeOverrides`.
interface PostTypeCandidate {
	postType: string | undefined;
	labels: PostTypeLabels;
}

function findMatchingCandidate(
	candidates: PostTypeCandidate[],
	labelKeys: string[],
	content: string
): PostTypeCandidate | undefined {
	return candidates.find( ( candidate ) =>
		labelKeys.some(
			( key ) =>
				candidate.labels?.[ key ] && candidate.labels[ key ] === content
		)
	);
}

function transformNotice(
	notice: Notice,
	candidates: PostTypeCandidate[]
): Notice {
	const overrides = getNoticeOverrides();
	// A plain lookup would resolve ids like `constructor` or `toString` to
	// an inherited `Object.prototype` member instead of `undefined`.
	if ( ! Object.prototype.hasOwnProperty.call( overrides, notice.id ) ) {
		return notice;
	}
	const override = overrides[ notice.id ];

	const matchedCandidate = override.labelKeys
		? findMatchingCandidate(
				candidates,
				override.labelKeys,
				notice.content
		  )
		: undefined;

	const rewriteText = ! override.labelKeys || !! matchedCandidate;

	const content =
		notice.id === 'editor-save' &&
		isTemplatePostType( matchedCandidate?.postType )
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
	candidates: PostTypeCandidate[]
): Notice[] {
	return notices.map( ( notice ) => transformNotice( notice, candidates ) );
}

function getStoreName( namespace: string | { name: string } ): string {
	return typeof namespace === 'object' ? namespace.name : namespace;
}

const getNoticesWithOverrides = createSelector(
	(
		notices: Notice[],
		currentPostType: string | undefined,
		currentLabels: PostTypeLabels,
		emailPostType: string | undefined,
		emailLabels: PostTypeLabels
	) => {
		const candidates: PostTypeCandidate[] = [
			{ postType: currentPostType, labels: currentLabels },
		];
		if ( emailPostType !== currentPostType ) {
			candidates.push( { postType: emailPostType, labels: emailLabels } );
		}
		return applyOverridesToNotices( notices, candidates );
	},
	(
		notices: Notice[],
		currentPostType: string | undefined,
		currentLabels: PostTypeLabels,
		emailPostType: string | undefined,
		emailLabels: PostTypeLabels
	) => [ notices, currentPostType, currentLabels, emailPostType, emailLabels ]
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
									undefined,
									undefined,
									undefined
								);
							}

							const getLabelsFor = (
								postType: string | undefined
							): PostTypeLabels =>
								postType
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

							// The post type currently being edited: navigating
							// from an email into its template (without a page
							// reload) changes what's on screen without
							// touching the email editor store's own post
							// type, so this can differ from the one below.
							const currentPostType = (
								originalSelect( CORE_EDITOR_STORE ) as
									| {
											getCurrentPostType?: () =>
												| string
												| undefined;
									  }
									| undefined
							 )?.getCurrentPostType?.();
							const currentLabels =
								getLabelsFor( currentPostType );

							// The post type the email editor was opened on. A
							// notice's text is written when the save happens,
							// from the labels of the post type being saved —
							// matching it against both candidates keeps the
							// wording correct regardless of which one is
							// current by the time this selector re-runs.
							const emailPostType = (
								originalSelect( emailEditorStoreName ) as
									| {
											getEmailPostType?: () =>
												| string
												| undefined;
									  }
									| undefined
							 )?.getEmailPostType?.();
							const emailLabels =
								emailPostType === currentPostType
									? currentLabels
									: getLabelsFor( emailPostType );

							return getNoticesWithOverrides(
								notices,
								currentPostType,
								currentLabels,
								emailPostType,
								emailLabels
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
