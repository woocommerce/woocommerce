import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@wordpress/ui';
import type { ReviewRecord } from '../../data';

type ReviewTimelineEventProps = {
	review: ReviewRecord;
};

/**
 * Renders the content of a single Review activity event in the Store
 * Activity timeline. Both the reviewer name (linking to the comment edit
 * screen) and the product name (linking to the product edit screen) come
 * back from the `_embed=up` payload on the REST response.
 */
export function ReviewTimelineEvent( { review }: ReviewTimelineEventProps ) {
	const reviewerName = review.author_name || __( 'Anonymous', 'woocommerce' );
	const productId = review.post;
	const productName = review._embedded?.up?.[ 0 ]?.title?.rendered;
	const reviewHref = `/wp-admin/comment.php?action=editcomment&c=${ review.id }`;
	const productHref = productId
		? `/wp-admin/post.php?post=${ productId }&action=edit`
		: undefined;

	return (
		<span>
			{ createInterpolateElement(
				__( '<reviewLink /> on <productLink />', 'woocommerce' ),
				{
					reviewLink: (
						<Link href={ reviewHref }>{ reviewerName }</Link>
					),
					productLink: productHref ? (
						<Link href={ productHref }>
							{ productName || __( 'Product', 'woocommerce' ) }
						</Link>
					) : (
						<span>
							{ __( 'Unknown product', 'woocommerce' ) }
						</span>
					),
				}
			) }
		</span>
	);
}
