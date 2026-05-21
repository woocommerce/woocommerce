import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@wordpress/ui';
import type { ProductReviewRecord } from '../../data';

type ReviewTimelineEventProps = {
	review: ProductReviewRecord;
};

/**
 * Renders the content of a single product-review activity event in the
 * Store Activity timeline. Both the reviewer name (linking to the comment
 * edit screen) and the product name (linking to the product edit screen)
 * come directly from the `/wc/v3/products/reviews` V3 response.
 */
export function ReviewTimelineEvent( { review }: ReviewTimelineEventProps ) {
	const reviewerName = review.reviewer || __( 'Anonymous', 'woocommerce' );
	const productId = review.product_id;
	const productName = review.product_name;
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
