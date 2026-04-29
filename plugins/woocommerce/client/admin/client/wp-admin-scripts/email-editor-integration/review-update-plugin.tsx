/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { EmailActionsFill } from '@woocommerce/email-editor';

/**
 * Internal dependencies
 */
import { ReviewDrawer } from './review-drawer';
import { useChangeSummary } from './hooks/use-change-summary';

/** Extract a human-readable title from a core-data post entity. */
function extractTitle( post: { title: unknown } ): string {
	const title = post.title;
	if ( typeof title === 'string' ) {
		return title;
	}
	if ( title && typeof title === 'object' ) {
		if ( 'rendered' in title && typeof title.rendered === 'string' ) {
			return title.rendered;
		}
		if ( 'raw' in title && typeof title.raw === 'string' ) {
			return title.raw;
		}
	}
	return '';
}

/**
 * Mounts the "Review template update" trigger button into the email
 * actions slot and renders the review drawer when clicked.
 *
 * Interim trigger — RSM-141 will replace this button with the design's
 * floating editor banner. Until then, this gives end-to-end testability.
 *
 * The button only appears when the change-summary reports at least one
 * delta and is not in fallback mode.
 */
export const ReviewUpdatePlugin = () => {
	const [ isDrawerOpen, setIsDrawerOpen ] = useState< boolean >( false );

	// Resolve the current woo_email post ID. The block editor's core/editor
	// store exposes it via getCurrentPostId(); we typecheck loosely because
	// the global is typed as `any` upstream.
	const postId = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const editorStore = ( select as any )( 'core/editor' );
		const id = editorStore?.getCurrentPostId?.();
		return typeof id === 'number' ? id : null;
	}, [] );

	const post = useSelect(
		( select ) => {
			if ( ! postId ) {
				return null;
			}
			return select( coreStore ).getEntityRecord(
				'postType',
				'woo_email',
				postId
			);
		},
		[ postId ]
	);

	const emailTitle =
		post && typeof post === 'object' && 'title' in post
			? extractTitle( post as { title: unknown } )
			: '';

	// Cheaply prefetch the summary so the button visibility tracks reality.
	// `enabled` stays true so we keep a fresh summary cached (the backend
	// transient invalidates on any post edit).
	const { summary } = useChangeSummary( postId, true );

	const totalChanges = summary
		? summary.copy_changes.length +
		  summary.added_blocks.length +
		  summary.removed_blocks.length +
		  summary.structural_changes.length
		: 0;

	const showTrigger =
		summary !== null && ! summary.is_fallback && totalChanges > 0;

	if ( ! postId ) {
		return null;
	}

	return (
		<>
			{ showTrigger && (
				<EmailActionsFill>
					<Button
						variant="secondary"
						onClick={ () => setIsDrawerOpen( true ) }
						__next40pxDefaultSize
					>
						{ __( 'Review template update', 'woocommerce' ) }
					</Button>
				</EmailActionsFill>
			) }
			<ReviewDrawer
				postId={ postId }
				emailTitle={ emailTitle }
				isOpen={ isDrawerOpen }
				onClose={ () => setIsDrawerOpen( false ) }
			/>
		</>
	);
};
