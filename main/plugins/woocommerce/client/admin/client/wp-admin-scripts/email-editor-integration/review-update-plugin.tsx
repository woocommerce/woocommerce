/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ReviewDrawer } from './review-drawer';
import { STORE_NAME } from './store';

/** Extract a human-readable title from a core-data post entity. */
function extractTitle( post: { title: unknown } ): string {
	const { title } = post;
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
 * Mounts the review drawer into the WooCommerce email editor's plugin
 * scope. The drawer's open / close state lives in the
 * `woocommerce/email-editor-integration` store, so any other surface
 * can open it via:
 *
 * ```
 * wp.data.dispatch( 'woocommerce/email-editor-integration' )
 *   .openReviewDrawer();
 * ```
 *
 * RSM-141 will wire the dispatch to the design's floating editor
 * banner. Until then, opening the drawer for testing happens from the
 * browser console using the same dispatch call.
 */
export const ReviewUpdatePlugin = () => {
	const { setReviewDrawerOpen } = useDispatch( STORE_NAME );
	const isDrawerOpen = useSelect(
		( select ) => select( STORE_NAME ).isReviewDrawerOpen(),
		[]
	);

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

	if ( ! postId ) {
		return null;
	}

	return (
		<ReviewDrawer
			postId={ postId }
			emailTitle={ emailTitle }
			isOpen={ isDrawerOpen }
			onOpenChange={ setReviewDrawerOpen }
		/>
	);
};
